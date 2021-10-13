<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:46
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Registrations\_RegistrationsController;
use App\Controllers\Registrations\_ValidationController;
use App\Policy\Auth;

$app->group(
    '/registrations', function () {
    $this->get(
        '/get',
        _RegistrationsController::class . ':getAll'
    )->add(Auth::class);
    $this->get(
        '/get/{id}',
        _RegistrationsController::class . ':get'
    )->add(Auth::class);
    $this->post('/validate', _ValidationController::class . ':postValidate');
    $this->get('/validate/{id}', _ValidationController::class . ':getValidate');
    $this->get('/validateBacklog', _ValidationController::class . ':backlogValidation');
}
);