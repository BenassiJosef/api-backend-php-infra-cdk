<?php

/**
 * Created by jamieaitken on 28/09/2018 at 14:09
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\MailChimp\MailChimpListController;
use App\Controllers\Integrations\MailChimp\MailChimpSetupController;

$this->group(
    '/mailchimp',
    function () {
        $this->put('', MailChimpSetupController::class . ':updateUserDetailsRoute');
        $this->get('', MailChimpSetupController::class . ':getUserDetailsRoute');
        $this->group(
            '/lists',
            function () {
                $this->get('', MailChimpListController::class . ':getExternalRoute');
                $this->group(
                    '/{serial}',
                    function () {
                        $this->get('', MailChimpListController::class . ':getAllRoute');
                        $this->put('', MailChimpListController::class . ':updateRoute');
                        $this->get('/{id}', MailChimpListController::class . ':getSpecificRoute');
                        $this->delete('/{id}', MailChimpListController::class . ':deleteRoute');
                    }
                );
            }
        );
    }
);
