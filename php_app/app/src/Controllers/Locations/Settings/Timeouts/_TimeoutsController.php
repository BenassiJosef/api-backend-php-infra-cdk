<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:41
 */

namespace App\Controllers\Locations\Settings\Timeouts;

use App\Controllers\Integrations\Mikrotik\_MikrotikTimeoutsController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Timeout\LocationTimeout;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _TimeoutsController extends _LocationSettingsController
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $serial      = $request->getAttribute('serial');
        $loggedIn    = $request->getAttribute('user');
        $body        = $request->getParsedBody();
        $networkType = $this->getType($serial);

        $send = $this->setTimeouts($serial, $networkType, $body);

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('timeout_updated', $send['message']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getTimeoutsRoute(Request $request, Response $response)
    {
        $send = $this->getTimeouts($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getTimeouts(string $serial)
    {
        $locationOtherId = $this->getLocationIdBySerial($serial);

        $getTimeouts = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationTimeout::class, 'u')
            ->where('u.locationOtherId = :i')
            ->setParameter('i', $locationOtherId)
            ->getQuery()
            ->getArrayResult();

        if (empty($getTimeouts)) {
            return Http::status(404, 'FAILED_TO_LOCATE_TIMEOUTS');
        }

        return Http::status(200, $getTimeouts);
    }

    /**
     * @param string $serial
     * @param int $type
     * @param array $body
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function setTimeouts(string $serial = '', int $type = 0, array $body = [])
    {

        $locationOtherId = $this->getLocationIdBySerial($serial);

        $defaultObj = [
            'session' => 0,
            'idle'    => 0
        ];

        if (!isset($body['paid'])) {
            $body['paid'] = $defaultObj;
        }
        if (!isset($body['free'])) {
            $body['free'] = $defaultObj;
        }

        $payload = [
            'free' => [
                'session' => $body['free']['session'],
                'idle'    => $body['free']['idle']
            ],
            'paid' => [
                'session' => $body['paid']['session'],
                'idle'    => $body['paid']['idle']
            ]
        ];

        $timeoutsSQL = $this->em->getRepository(LocationTimeout::class)->findBy([
            'locationOtherId' => $locationOtherId
        ]);

        foreach ($timeoutsSQL as $to) {
            $to->idle    = $body[$to->kind]['idle'];
            $to->session = $body[$to->kind]['session'];
        }

        $this->em->flush();

        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $mikrotikController = new _MikrotikTimeoutsController($this->em);
                    $mikrotikController->setTimeouts($serial, $type, $body['free'], $body['paid']);
                    break;
                case 'UNIFI':
                    break;
                case 'OPENMESH':
                    break;
            }
        }

        return Http::status(200, $body);
    }

    private function getLocationIdBySerial(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.other')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult()[0]['other'];
    }


    public static function defaultFreeTimeout(string $locationOtherId)
    {
        return new LocationTimeout(
            LocationTimeout::defaultFreeIdle(),
            LocationTimeout::defaultFreeSession(),
            $locationOtherId,
            'free'
        );
    }

    public static function defaultPaidTimeout(string $locationOtherId)
    {
        return new LocationTimeout(
            LocationTimeout::defaultPaidIdle(),
            LocationTimeout::defaultPaidSession(),
            $locationOtherId,
            'paid'
        );
    }
}
