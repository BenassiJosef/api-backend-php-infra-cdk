<?php
/**
 * Created by jamieaitken on 2019-07-04 at 13:13
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Airship\AirshipGroupController;
use App\Controllers\Integrations\Airship\AirshipSetupController;

$this->group(
    '/airship', function () {
    $this->put('', AirshipSetupController::class . ':updateUserDetailsRoute');
    $this->get('', AirshipSetupController::class . ':getUserDetailsRoute');
    $this->group(
        '/lists', function () {
        $this->get('', AirshipGroupController::class . ':getExternalRoute');
        $this->group(
            '/{serial}', function () {
            $this->get('', AirshipGroupController::class . ':getAllRoute');
            $this->put('', AirshipGroupController::class . ':updateRoute');
            $this->get('/{id}', AirshipGroupController::class . ':getSpecificRoute');
            $this->delete('/{id}', AirshipGroupController::class . ':deleteRoute');
        }
        );
    }
    );
}
);