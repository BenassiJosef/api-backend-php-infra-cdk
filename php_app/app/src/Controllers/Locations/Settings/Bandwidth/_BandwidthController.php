<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:41
 */

namespace App\Controllers\Locations\Settings\Bandwidth;

use App\Controllers\Integrations\Mikrotik\_MikrotikBandwidthController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\Locations\Bandwidth\LocationBandwidth;
use App\Models\Locations\LocationSettings;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _BandwidthController extends _LocationSettingsController
{

    /**
     * _TimeoutsController constructor.
     * @param EntityManager $em
     */

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */

    public function updateRoute(Request $request, Response $response)
    {
        $serial      = $request->getAttribute('serial');
        $loggedIn    = $request->getAttribute('user');
        $body        = $request->getParsedBody();
        $networkType = $this->getType($serial);
        $send        = $this->setBandwidth($serial, $networkType, $body);

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('bandwidth_updated', $send['message']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $serial)
    {
        $locationOtherId = $this->getLocationIdBySerial($serial);

        $fetch = $this->nearlyCache->fetch($serial . ':bandwidth');

        if (!is_bool($fetch)) {
            return Http::status(200, $fetch);
        }

        $bandwidths = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationBandwidth::class, 'u')
            ->where('u.locationOtherId = :l')
            ->setParameter('l', $locationOtherId)
            ->getQuery()
            ->getArrayResult();

        if (empty($bandwidths)) {
            return Http::status(404, 'NO_BANDWIDTHS_FOUND');
        }

        $this->nearlyCache->save($serial . ':bandwidth', $bandwidths);

        return Http::status(200, $bandwidths);
    }

    /**
     * @param string $serial
     * @param int $type
     * @param array $body
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function setBandwidth(string $serial = '', int $type = 0, array $body = [])
    {
        $locationOtherId = $this->getLocationIdBySerial($serial);

        $defaultObj = [
            'upload'   => 0,
            'download' => 0
        ];

        if (!isset($body['paid'])) {
            $body['paid'] = $defaultObj;
        }
        if (!isset($body['free'])) {
            $body['free'] = $defaultObj;
        }

        $payload = [
            'free' => [
                'upload'   => $body['free']['upload'],
                'download' => $body['free']['download']
            ],
            'paid' => [
                'upload'   => $body['paid']['upload'],
                'download' => $body['paid']['download']
            ]
        ];

        $bandwidthsSQL = $this->em->getRepository(LocationBandwidth::class)->findBy([
            'locationOtherId' => $locationOtherId
        ]);

        foreach ($bandwidthsSQL as $bw) {
            $bw->download = $body[$bw->kind]['download'];
            $bw->upload   = $body[$bw->kind]['upload'];
        }

        $this->nearlyCache->delete($serial . ':bandwidth');

        $this->em->flush();

        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $mikrotikController = new _MikrotikBandwidthController($this->em);
                    $mikrotikController->setBandwidths($serial, $type, $body['free'], $body['paid']);
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

    public static function defaultFreeBandwidth(string $locationOtherId)
    {
        return new LocationBandwidth(
            LocationBandwidth::defaultFreeDownload(),
            LocationBandwidth::defaultFreeUpload(),
            $locationOtherId,
            'free'
        );
    }

    public static function defaultPaidBandwidth(string $locationOtherId)
    {
        return new LocationBandwidth(
            LocationBandwidth::defaultPaidDownload(),
            LocationBandwidth::defaultPaidUpload(),
            $locationOtherId,
            'paid'
        );
    }
}
