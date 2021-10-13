<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/12/2016
 * Time: 17:37
 */

namespace App\Controllers\Integrations\Radius;

use App\Models\Locations\Informs\Inform;
use App\Models\Radius\RadiusAccounting;
use App\Models\RadiusVendor;
use App\Package\Vendors\Information;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\RadiusEngine;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _RadiusController
{

    protected $em;
    protected $connectCache;
    protected $radius;

    /**
     * @var Information $information
     */
    protected $information;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
        $this->radius       = RadiusEngine::getInstance();
        $this->information = new Information($this->em);
    }

    public function getSecretRoute(Request $request, Response $response)
    {

        $serial = $request->getAttribute('serial');
        $send   = $this->getSecret($serial);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSecretInDashboardRoute(Request $request, Response $response)
    {
        $send = $this->getSecret($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateUserDataRoute(Request $request, Response $response)
    {
        $send = $this->updateUserDataFromRadiusAccounting();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateSecretRoute(Request $request, Response $response)
    {
        $send = $this->updateSecret($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSecret(string $serial)
    {
        $cachedSerial = $this->connectCache->fetch('locations:' . $serial . ':secret');

        if (!is_bool($cachedSerial)) {
            return Http::status(200, $cachedSerial['secret']);
        }

        $inform =  $this->information->getFromSerial($serial);

        if (is_null($inform)) {
            return Http::status(404, 'SECRET_NOT_FOUND');
        }

        $this->connectCache->save('locations:' . $serial . ':secret', ['secret' => $inform->getRadiusSecret()]);

        return Http::status(200, $inform->getRadiusSecret());
    }

    public function updateSecret(string $serial, array $updateBody)
    {
        $inform =  $this->information->getFromSerial($serial);

        if (is_null($inform)) {
            return Http::status(404, 'NO_SECRET_ASSOCIATED_WITH_THIS_SERIAL');
        }

        $inform->setRadiusSecret($updateBody['secret']);
        $this->em->persist($inform);
        $this->em->flush();

        $this->connectCache->delete('locations:' . $serial . ':secret');

        return Http::status(200, $updateBody['secret']);
    }

    public function updateUserDataFromRadiusAccounting()
    {
        $newDateNow = new \DateTime();
        $newDateTen = new \DateTime();
        $newDateTen->modify('-180 minutes');
        $getDataFromAccounting = $this->radius->createQueryBuilder($this->em)
            ->select('acc')
            ->from(RadiusAccounting::class, 'acc')
            ->where('acc.acctUpdateTime BETWEEN :start AND :end')
            ->andWhere("acc.userName != ''")
            ->andWhere('LENGTH(acc.userName) > :number')
            ->setParameter('number', 14)
            ->setParameter('start', $newDateTen)
            ->setParameter('end', $newDateNow)
            ->getQuery()
            ->getArrayResult();

        if (empty($getDataFromAccounting)) {
            return Http::status(400);
        }
        $builder = $this->em->getConnection();
        $count   = 0;
        $updated = [];
        foreach ($getDataFromAccounting as $data) {
            $count     += 1;
            $serial    = strtoupper(substr(
                $data['userName'],
                strlen($data['userName']) - 12
            ));
            $profileId = (int) substr($data['userName'], 0, strlen($data['userName']) - strlen($serial));

            $query = 'UPDATE user_data u SET 
                  u.data_down = :download, 
                  u.data_up = :upload, 
                  u.lastupdate = NOW(),
                  u.ip = :ipAddress
                  WHERE profileId = :id
                  AND serial = :serial 
                  ORDER BY u.timestamp 
                  DESC LIMIT 1';


            $stmt = $builder->prepare($query);
            $update    = [
                'download'  => $data['acctOutputOctets'],
                'upload'    => $data['acctInputOctets'],
                'serial'    => $serial,
                'id'        => $profileId,
                'ipAddress' => $data['framedIpAddress']
            ];
            $updated[] = $update;
            $stmt->execute($update);
        }
        $builder->close();

        return Http::status(200, [
            'Updated' => 'UPDATED ' . $count,
            'records' => $updated
        ]);
    }
}
