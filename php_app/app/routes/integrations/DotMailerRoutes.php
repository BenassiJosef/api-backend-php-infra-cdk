<?php
/**
 * Created by jamieaitken on 01/10/2018 at 16:13
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\dotMailer\DotMailerAddressBookController;
use App\Controllers\Integrations\dotMailer\DotMailerSetupController;

$this->group(
    '/dotmailer', function () {
    $this->put('', DotMailerSetupController::class . ':updateUserDetailsRoute');
    $this->get('', DotMailerSetupController::class . ':getUserDetailsRoute');
    $this->group(
        '/lists', function () {
        $this->get(
            '',
            DotMailerAddressBookController::class . ':getExternalRoute'
        );
        $this->group(
            '/{serial}', function () {
            $this->get(
                '',
                DotMailerAddressBookController::class . ':getAllRoute'
            );
            $this->put(
                '',
                DotMailerAddressBookController::class . ':updateRoute'
            );
            $this->get('/{id}', DotMailerAddressBookController::class . ':getSpecificRoute');
            $this->delete('/{id}', DotMailerAddressBookController::class . ':deleteRoute');
        }
        );
    }
    );
}
);