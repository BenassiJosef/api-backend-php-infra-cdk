<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 28/04/2017
 * Time: 19:15
 */

namespace App\Controllers\Integrations\Mikrotik;

use App\Controllers\Clients\_ClientsUpdateController;
use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _MikrotikUserDataController
{
    protected $em;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    function getRoute(Request $request, Response $response, $args)
    {
        $body = $request->getAttribute('payload');
        $resp = $this->routeRequest($body);

        $this->em->clear();

        return $response->withJson($resp, $resp['status']);
    }

    function postRoute(Request $request, Response $response, $args)
    {
        $body = $request->getBody();
        $resp = $this->routeRequest($body);

        $this->em->clear();

        return $response->withJson($resp, $resp['status']);
    }

    function routeRequest($data)
    {
        $data      = substr($data, 0, -1);
        $data      = str_replace("'", '"', $data);
        $json      = '{' . $data . '}';
        $obj_array = json_decode($json);

        $client = new _ClientsUpdateController($this->em);

        //Check each value
        foreach ($obj_array as $key => $value) {
            //GET DATASET FROM EACH VALUE
            $array = explode(',', $value);

            $mac    = $array[0];
            $ip     = $array[1];
            $down   = $array[2];
            $up     = $array[3];
            $serial = $array[5];

            $virtualSerial = $this->em->createQueryBuilder()
                ->select('u.virtualSerial')
                ->from(MikrotikSymlinkSerial::class, 'u')
                ->where('u.physicalSerial = :serial')
                ->setParameter('serial', $serial)
                ->getQuery()
                ->getArrayResult();

            if (empty($virtualSerial)) {
                $virtualSerial = new MikrotikSymlinkSerial($serial);
                $this->em->persist($virtualSerial);
                $this->em->flush();
                $virtualSerial = $virtualSerial->virtualSerial;
            } else {
                $virtualSerial = $virtualSerial[0]['virtualSerial'];
            }

            $client->update($down, $up, $mac, $virtualSerial, $ip);
        }

        return Http::status(200, 'PAYLOAD_COMPLETE');
    }
}
