<?php
/**
 * Created by jamieaitken on 19/03/2018 at 12:54
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\UniFi\_UniFiController;

$this->group(
    '/unifi', function () {
    $this->get('', _UniFiController::class . ':getUsersControllersRoute');
    $this->post('', _UniFiController::class . ':setUpControllerRoute');
    $this->group(
        '/{controllerId}', function () {
        $this->delete('', _UniFiController::class . ':deleteControllerRoute');
        $this->get('', _UniFiController::class . ':getControllerRoute');
        $this->put('', _UniFiController::class . ':updateControllerRoute');
        $this->group(
            '/location', function () {

            $this->get('', App\Controllers\Integrations\UniFi\_UniFiController::class . ':listSitesRoute');
            $this->get('/ssid', App\Controllers\Integrations\UniFi\_UniFiController::class . ':listSsidRoute');

            $this->put(
                '/{serial}',
                _UniFiController::class . ':linkSerialWithControllerRoute'
            );
            $this->get(
                '/{serial}',
                _UniFiController::class . ':getCurrentSiteSetupRoute'
            );
        }
        );
    }
    );
}
);