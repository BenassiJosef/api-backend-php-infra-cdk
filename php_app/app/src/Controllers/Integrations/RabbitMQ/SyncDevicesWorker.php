<?php
/**
 * Created by jamieaitken on 07/06/2018 at 13:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\UniFi\_UniFi;
use App\Controllers\Integrations\UniFi\_UniFiSettingsController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class SyncDevicesWorker
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $settingsCtrl = new _UniFiSettingsController($this->em);
        $settingsResp = $settingsCtrl->settings($body['serial']);

        if ($settingsResp['status'] === 404) {
            return $settingsResp;
        }

        $settings = $settingsResp['message'];
        $unifi    = new _UniFi($settings['username'], $settings['password'], $settings['hostname'],
            $settings['unifiId']);

        if ($unifi->login === false) {
            return Http::status(409, 'DETAILS_INCORRECT_FOR_' . $body['serial']);
        }

        $devices = $unifi->listAps();
        $resp    = ['nodesAdded' => 0, 'errors' => []];
        if (empty($devices) && is_bool($devices)) {
            return false;
        }

        $deviceCtrl = new _LocationsDevicesController($this->em);

        $deviceCtrl->deleteDevicesBySerial($body['serial']);

        $newDevices = [];

        foreach ($devices as $device) {
            $mac  = strtoupper($device->mac);
            $name = '';
            if (property_exists($device, 'name')) {
                $name = $device->name;
            }
            $newDevices[$mac] = [
                'alias' => $name,
                'mac'   => $mac
            ];
            $deviceCtrl->deleteDeviceByMac($mac);
        }

        foreach ($newDevices as $device) {
            $added = $deviceCtrl->addDevice([
                'alias' => $device['alias'],
                'mac'   => $device['mac']
            ], $body['serial']);

            if ($added['status'] === 200) {
                $resp['nodesAdded']++;
            }
        }

        return true;
    }
}