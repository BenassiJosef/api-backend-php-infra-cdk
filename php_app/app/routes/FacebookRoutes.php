<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Facebook\_FacebookLoginController;
use App\Controllers\Integrations\Facebook\_FacebookPagesController;
use App\Policy\Auth;


$app->group('/facebook', function () {
    $this->get('/authorize/{orgId}', _FacebookLoginController::class . ':authorizeRoute')
        ->add(Auth::class);
    $this->get('/callback', _FacebookLoginController::class . ':callback');
    $this->group('/accounts/{orgId}', function () {
        $this->get('', _FacebookLoginController::class . ':getTokensRoute');
        $this->group('/{token}/pages', function () {
            $this->get('', _FacebookPagesController::class . ':getPagesRoute');
            $this->put('/{pageId}', _FacebookPagesController::class . ':updatePagesRoute');
        });
    })
        ->add(Auth::class);
});