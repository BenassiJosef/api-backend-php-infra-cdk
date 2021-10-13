<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:45
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Auth\_PasswordController;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\LegacyCompatibilityMiddleware;

$app->group(
    '/password', function () {
    $this->get('/forgot', _PasswordController::class . ':forgotPasswordRoute');
    $this->group(
        '/reset', function () {
        $this->get('/{token}', _PasswordController::class . ':resetPasswordRoute');
        $this->put('/{token}', _PasswordController::class . ':updatePasswordFromTokenRoute');
    }
    );
    $this->put(
        '/{uid}',
        _PasswordController::class . ':masterChangeRoute'
    )
         ->add(LegacyCompatibilityMiddleware::class)
         ->add(AuthMiddleware::class);
    $this->put('', _PasswordController::class . ':changePasswordRoute')
         ->add(LegacyCompatibilityMiddleware::class)
         ->add(AuthMiddleware::class);
}
);