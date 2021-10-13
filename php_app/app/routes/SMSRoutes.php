<?php
/**
 * Created by jamieaitken on 25/10/2018 at 13:30
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\SMS\_SMSController;

$app->group(
    '/sms', function () {
    $this->post('', _SMSController::class . ':sendRoute');
    $this->get('', _SMSController::class . ':validateRoute');
    $this->group(
        '/verification', function () {
        $this->post('', _SMSController::class . ':verifyRoute');
        $this->put('', _SMSController::class . ':checkVerifyRoute');
    }
    );
}
);