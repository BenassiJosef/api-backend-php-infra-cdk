<?php

/**
 * Created by jamieaitken on 25/10/2018 at 13:26
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Clients\_ClientsController;
use App\Controllers\Integrations\Mikrotik\_MikrotikInformController;
use App\Controllers\Integrations\Mikrotik\_MikrotikUserDataController;
use App\Controllers\Integrations\OpenMesh\_OpenMeshInformController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Policy\Auth;
use App\Policy\isIgniteNet;
use App\Policy\isMikrotik;

$app->group(
    '/mikrotik',
    function () {
        $this->get(
            '/clients/{payload}',
            _MikrotikUserDataController::class . ':getRoute'
        );
        $this->post('/clients', _MikrotikUserDataController::class . ':postRoute');
    }
);

$app->group(
    '/ignitenet/{serial}',
    function () {
        $this->put(
            '/clients',
            App\Controllers\Integrations\IgniteNet\_IgniteNetController::class . ':updateClientDataRoute'
        );
    }
)->add(isIgniteNet::class);

$app->group(
    '/inform/{serial}',
    function () {
        $this->put(
            '/ignitenet',
            App\Controllers\Integrations\IgniteNet\_IgniteNetInformController::class . ':igniteNetInform'
        )->add(Auth::class);
        $this->get(
            '/mikrotik/{cpu}',
            App\Controllers\Integrations\Mikrotik\_MikrotikInformController::class . ':mikrotikInform'
        )->add(isMikrotik::class);
    }
);

$app->group(
    '/deform/{serial}',
    function () {
        $this->put(
            '/ignitenet',
            App\Controllers\Integrations\IgniteNet\_IgniteNetInformController::class . ':igniteNetDeform'
        );
    }
)->add(Auth::class);

$app->group(
    '/clients',
    function () {
        $this->group(
            '/session',
            function () {
                $this->post('', _ClientsController::class . ':createRoute');
            }
        );
    }
);

/**
 *
 * LEGACY ROUTE REQUIRED FOR DOMAIN MIGRATION
 *
 */
$app->get(
    '/devices/checkin/{id}/{status}',
    _LocationsDevicesController::class . ':getLegacyUpdateDeviceRoute'
)->add(isMikrotik::class);
$app->get(
    '/inform/host_data/{payload}',
    _MikrotikUserDataController::class . ':getRoute'
)->add(isMikrotik::class);
$app->post(
    '/inform/host_data_openmesh/{ap}',
    _OpenMeshInformController::class . ':openMeshInformRoute'
);

$app->get(
    '/inform/checkin/{serial}/{cpu}/{model}',
    _MikrotikInformController::class . ':mikrotikInformLegacy'
);
