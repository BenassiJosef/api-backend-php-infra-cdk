<?php
/**
 * Created by jamieaitken on 19/03/2018 at 12:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\PayPal\_PayPalController;
use App\Models\Role;

$this->group(
    '/paypal', function () {
    $this->get('', _PayPalController::class . ':retrieveAccountsRoute');
    $this->post('', _PayPalController::class . ':createAccountRoute');
    $this->group(
        '/{id}', function () {
        $this->put('', _PayPalController::class . ':updateAccountRoute');
        $this->get('', _PayPalController::class . ':retrieveAccountRoute');
        $this->delete(
            '',
            _PayPalController::class . ':deleteAccountRoute'
        )->add(new App\Policy\Role(Role::LegacyAdmin));
        $this->group(
            '/location', function () {
            $this->get('/{serial}', _PayPalController::class . ':getSerialRoute');
            $this->put(
                '/{serial}',
                _PayPalController::class . ':linkAccountWithSerialRoute'
            );
        }
        );
    }
    );
}
)->add(new App\Policy\Role(Role::LegacyModerator)); //TODO: middleware2