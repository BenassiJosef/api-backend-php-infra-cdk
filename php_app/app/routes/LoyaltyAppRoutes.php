<?php

use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Package\AppleSignIn\AppleSignIn;
use App\Package\Loyalty\Presentation\AppStampCardController;
use App\Package\Profile\ProfileChecker;
use App\Policy\NearlyLogInService;

$app->group(
    '/loyalty',
    function () {
        $this->post('/apple-auth', AppleSignIn::class . ':postTokenPath');
        $this->post('/register', ProfileChecker::class . ':createProfile');
        $this->get('/check-email', ProfileChecker::class . ':checkEmailRoute');
        $this->get('/discovery', LocationPositionController::class . ':getClosestLoyaltyLocations');
        $this->group(
            '/{id}',
            function () {
                $this->get('', ProfileChecker::class . ':getMe');
                $this->put('/password', ProfileChecker::class . ':updatePassword');
                $this->group(
                    '/schemes',
                    function () {
                        $this->get('', AppStampCardController::class . ':getCards');
                        $this->post('/{secondaryId}', AppStampCardController::class . ':stamp');
                        $this->get('/{secondaryId}', AppStampCardController::class . ':getScheme');
                        $this->delete('/{schemeId}', AppStampCardController::class. ':removeScheme');
                        $this->post('/{schemeId}/rewards/{rewardId}', AppStampCardController::class . ':redeemReward');
                    }
                );
                $this->group('/notifications', function () {
                    $this->put('/subscribe', ProfileChecker::class . ':subscribeToNotifications');
                });
            }
        )->add(NearlyLogInService::class);
    }
);
