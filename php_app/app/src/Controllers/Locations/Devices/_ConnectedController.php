<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 24/03/2017
 * Time: 10:06
 */

namespace App\Controllers\Locations\Devices;

use App\Controllers\Integrations\UniFi\_UniFiConnectedController;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\Device\DeviceBrowser;
use App\Models\Device\DeviceOs;
use App\Models\User\UserAgent;
use App\Models\User\UserDevice;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ConnectedController extends _LocationSettingsController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function getRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');
        $send   = $this->getConnected($serial);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getConnected(string $serial)
    {
        $newDateTime = new \DateTime();

        $settings = new _LocationSettingsController($this->em);
        $vendor   = $settings->getVendor($serial);
        $mins     = '-10 minutes';

        if ($vendor !== false) {
            switch ($vendor) {
                case 'MIKROTIK':
                    break;
                case 'UNIFI':
                    $mins = '-20 minutes';
                    break;
                case 'OPENMESH':
                    break;
            }
        }

        $connected = $this->em->createQueryBuilder()
            ->select('u.lastupdate, u.timestamp, SUM(COALESCE(u.dataDown, 0)) AS download, SUM(COALESCE(u.dataUp, 0)) 
            AS upload, u.mac, ud.model as device, do.name as os, db.name as browser, u.ip, p.first, p.last, p.id, p.email')
            ->from(UserData::class, 'u')
            ->join(UserProfile::class, 'p', 'WITH', 'u.profileId = p.id')
            ->leftJoin(UserDevice::class, 'ud', 'WITH', 'ud.mac = u.mac')
            ->leftJoin(UserAgent::class, 'ua', 'WITH', 'ua.userDeviceId = ud.id')
            ->leftJoin(DeviceOs::class, 'do', 'WITH', 'do.id = ua.deviceOsId')
            ->leftJoin(DeviceBrowser::class, 'db', 'WITH', 'db.id = ua.deviceBrowserId')
            ->where('u.serial = :serial')
            ->andWhere('u.lastupdate > :now')
            ->setParameter('now', $newDateTime->modify($mins))
            ->setParameter('serial', $serial)
            ->groupBy('u.mac')
            ->getQuery()
            ->getArrayResult();

        $this->em->flush();

        if (!empty($connected)) {

            $settings          = new _LocationSettingsController($this->em);
            $vendor            = $settings->getVendor($serial);
            $connectedResponse = $connected;
            if ($vendor === 'UNIFI') {
                $unifiConnected = new _UniFiConnectedController($this->em);
                $unifiConnected->getConnected($serial);
                if (!empty($unifiConnected->clients)) {
                    $connectedResponse = $unifiConnected->mergeClients($connectedResponse);
                }
            }

            return Http::status(200, $connectedResponse);
        }

        return Http::status(204, 'NO_USERS_CONNECTED');
    }
}
