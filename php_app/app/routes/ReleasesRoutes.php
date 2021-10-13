<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:51
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Notifications\_ChangelogController;
use App\Controllers\Notifications\_ReleaseNotifyController;
use App\Policy\Auth;

$app->group(
    '/releases', function () {
    $this->get('/hasSeen', _ReleaseNotifyController::class . ':hasSeenReleaseRoute');
    $this->get(
        '/loadNotifications',
        _ReleaseNotifyController::class . ':loadNotificationsRoute'
    );
    $this->get(
        '/notificationStatus',
        _ReleaseNotifyController::class . ':notificationStatusRoute'
    );
}
)->add(Auth::class);

$app->group(
    '/changelog', function () {
    $this->get('', _ChangelogController::class . ':getRoute');
}
);