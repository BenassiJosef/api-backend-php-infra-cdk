<?php
/**
 * Created by jamieaitken on 10/10/2018 at 09:33
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactListController;
use App\Controllers\Integrations\CampaignMonitor\CampaignMonitorSetupController;

$this->group(
    '/campaignMonitor', function () {
    $this->put(
        '',
        CampaignMonitorSetupController::class . ':updateUserDetailsRoute'
    );
    $this->get(
        '',
        CampaignMonitorSetupController::class . ':getUserDetailsRoute'
    );
    $this->group(
        '/lists', function () {
        $this->get(
            '',
            CampaignMonitorContactListController::class . ':getExternalRoute'
        );
        $this->group(
            '/{serial}', function () {
            $this->get(
                '',
                CampaignMonitorContactListController::class . ':getAllRoute'
            );
            $this->put(
                '',
                CampaignMonitorContactListController::class . ':updateRoute'
            );
            $this->get(
                '/{id}',
                CampaignMonitorContactListController::class . ':getSpecificRoute'
            );
            $this->delete(
                '/{id}',
                CampaignMonitorContactListController::class . ':deleteRoute'
            );
        }
        );
    }
    );
}
);