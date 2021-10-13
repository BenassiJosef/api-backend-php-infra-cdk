<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:42
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Radius\_RadiusController;
use App\Controllers\Schedule\_DeformController;
use App\Controllers\Schedule\_EmailReports;
use App\Controllers\Schedule\_PartnerNetRevenue;
use App\Controllers\Schedule\_PostCodeBuilder;
use App\Controllers\Schedule\_ValidationTimeoutsController;
use App\Controllers\Schedule\QuoteScheduler;
use App\Controllers\Schedule\ReviewSchedule;
use App\Policy\isLocal;

$app->group(
    '/schedule', function () {
    $this->get('/emailReports', _EmailReports::class . ':runRoute');
    $this->get('/deform', _DeformController::class . ':getRoute');
    $this->get('/quotes', QuoteScheduler::class . ':runRoute');
    $this->get('/validationTimeout', _ValidationTimeoutsController::class . ':getRoute');
    $this->get('/postcode', _PostCodeBuilder::class . ':getPostCodeRoute');
    $this->get(
        '/radiusUserData',
        _RadiusController::class . ':updateUserDataRoute'
    );
    $this->get('/partnerRevenue', _PartnerNetRevenue::class . ':runRoute');
    $this->get('/remove-incomplete-campaigns', App\Controllers\Schedule\RemoveIncompleteCampaigns::class . ':runRoute');
    $this->get('/reviews', ReviewSchedule::class . ':getReviewsRoute');
}
)
    ->add(isLocal::class);
