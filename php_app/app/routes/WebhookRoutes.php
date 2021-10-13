<?php

/**
 * Created by jamieaitken on 25/10/2018 at 13:23
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Billing\Webhooks\ChargeBeeWebHookController;
use App\Controllers\Integrations\SNS\_QueueController;

$app->group(
    '/webhooks',
    function () {
        $this->post('/chargebee', ChargeBeeWebHookController::class . ':receiveWebHook');
        $this->post('/blackbx', _QueueController::class . ':');
        $this->post('/sns', _QueueController::class . ':postRoute');
    }
);
