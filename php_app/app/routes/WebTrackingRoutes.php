<?php

use App\Controllers\WebTracker\WebTrackingController;
use App\Policy\CookieMiddleware;
use App\Package\WebTracking\Tracking;

$app->group('/website', function () {
    $this->get('/cookie', WebTrackingController::class . ':getCookieRequest');
    $this->post('/track', Tracking::class . ':createEvent');
    $this->post('/profile', Tracking::class . ':assignProfileToCookie');
    $this->post('/org/{orgId}', WebTrackingController::class . ':createWebsiteFromOrg');

})->add(CookieMiddleware::class);

