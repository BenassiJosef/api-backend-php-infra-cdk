<?php
/**
 * Created by jamieaitken on 19/03/2018 at 12:37
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Payments\_PaymentsController;
use App\Controllers\User\_UserController;
use App\Package\Auth\AuthMiddleware;
use App\Package\Profile\Data\Presentation\DataController;

$app->group(
    '/subject/{profileId}',
    function () {
        $this->get('', DataController::class . ':getData');
        $this->delete('', DataController::class . ':forget');
    }
)->add(AuthMiddleware::class);

$app->group(
    '/user', function () {
    $this->group(
        '/{id}', function () {
        $this->get('/summary', _UserController::class . ':userSummaryRoute');
        $this->group(
            '/devices', function () {
            $this->get('', _UserController::class . ':loadUserDevicesRoute');
            $this->put('/block', _UserController::class . ':blockUserRoute');
            $this->put('/auth', _UserController::class . ':changeAuthorisedStateRoute');
        }
        );
        $this->group(
            '/payments', function () {
            $this->get('', _PaymentsController::class . ':getAll');
            $this->get('/{paymentId}', App\Controllers\Payments\_PaymentsController::class . ':get');
        }
        );
        $this->group(
            '/connections', function () {
            $this->get('', _UserController::class . ':loadUserConnectionLogRoute');
        }
        );
        $this->group(
            '/usage', function () {
            $this->get('', _UserController::class . ':loadUserDataUsageRoute');
        }
        );
        $this->group(
            '/marketing', function () {
            $this->get('', _UserController::class . ':loadUserMarketingDataRoute');
        }
        );
        $this->get('/download', _UserController::class . ':exportUserReportRoute');
    }
    );
    $this->get('/search', App\Controllers\User\_UserController::class . ':userSearchRoute');
}
)
    ->add(App\Policy\Auth::class);