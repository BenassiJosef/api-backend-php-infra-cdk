<?php
/**
 * Created by jamieaitken on 26/09/2018 at 16:19
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Textlocal\TextLocalGroupController;
use App\Controllers\Integrations\Textlocal\TextLocalSetupController;

$this->group(
    '/textlocal', function () {
    $this->put('', TextLocalSetupController::class . ':updateUserDetailsRoute');
    $this->get('', TextLocalSetupController::class . ':getUserDetailsRoute');
    $this->group(
        '/lists', function () {
        $this->get('', TextLocalGroupController::class . ':getExternalRoute');
        $this->group(
            '/{serial}', function () {
            $this->get('', TextLocalGroupController::class . ':getAllRoute');
            $this->put('', TextLocalGroupController::class . ':updateRoute');
            $this->get(
                '/{id}',
                TextLocalGroupController::class . ':getSpecificRoute'
            );
            $this->delete(
                '/{id}',
                TextLocalGroupController::class . ':deleteRoute'
            );
        }
        );
    }
    );
}
);