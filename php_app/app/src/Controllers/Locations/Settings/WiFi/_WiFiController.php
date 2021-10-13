<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:41
 */

namespace App\Controllers\Locations\Settings\WiFi;

use App\Controllers\Integrations\Mikrotik\_MikrotikWiFiController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _WiFiController extends _LocationSettingsController
{
    protected $connectCache;

    public function __construct(EntityManager $em)
    {
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
        parent::__construct($em);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $serial   = $request->getAttribute('serial');
        $loggedIn = $request->getAttribute('user');
        $body     = $request->getParsedBody();

        $shouldHave = ['ssid', 'disabled'];

        $validation = Validation::bodyCheck($request, $shouldHave);

        if ($validation !== true) {
            $send = Http::status(400, $validation);
        } else {
            $this->connectCache->deleteMultiple([
                $loggedIn['uid'] . ':location:accessibleLocations',
                $loggedIn['uid'] . ':marketing:accessibleLocations'
            ]);
            $send = $this->updateWiFi($serial, $body['disabled'], $body['ssid']);
        }

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('wifi_updated', $send);

        $this->em->clear();

        return $response->withStatus($send['status'])->withJson($send);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateWiFi($serial, $disabled, $ssid)
    {

        if (strlen($ssid) > 32) {
            return Http::status(409, 'SSID_IS_TOO_LONG');
        }

        $location = $this->getWiFiIdFromSerial($serial);

        $getWiFiSettings = $this->em->getRepository(LocationWiFi::class)->findOneBy([
            'id' => $location[0]['wifi']
        ]);

        $settings = [
            'disabled' => $disabled,
            'ssid'     => $ssid
        ];

        $getWiFiSettings->disabled = $disabled;
        $getWiFiSettings->ssid     = $ssid;

        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $mikrotikController = new _MikrotikWiFiController($this->em);
                    $mikrotikController->setWiFi($serial, $disabled, $ssid);
                    break;
                case 'UNIFI':
                    break;
                case 'OPENMESH':
                    break;
            }
        }

        $this->nearlyCache->delete($serial . ':wifi');

        $this->em->flush();

        return Http::status(200, $settings);
    }

    public function get(string $serial)
    {
        $location = $this->getWiFiIdFromSerial($serial);

        $fetchWifi = $this->nearlyCache->fetch($serial . ':wifi');

        if (!is_bool($fetchWifi)) {
            return Http::status(200, $fetchWifi);
        }

        $getWiFiSettings = $this->em->getRepository(LocationWiFi::class)->findOneBy([
            'id' => $location[0]['wifi']
        ]);

        if (is_null($getWiFiSettings)) {
            return Http::status(404, 'CAN_NOT_LOCATE_WIFI_SETTINGS_FOR_SERIAL');
        }

        $arrayCopyOfWifi = $getWiFiSettings->getArrayCopy();

        $this->nearlyCache->save($serial . ':wifi', $arrayCopyOfWifi);

        return Http::status(200, $arrayCopyOfWifi);
    }

    private function getWiFiIdFromSerial(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.wifi')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult();
    }

    public static function defaultWiFi(string $serial)
    {
        return new LocationWiFi(true, $serial);
    }
}
