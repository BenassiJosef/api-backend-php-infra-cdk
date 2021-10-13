<?php
/**
 * Created by jamieaitken on 09/10/2018 at 12:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\ConstantContact\ConstantContactAuthorize;
use App\Controllers\Integrations\ConstantContact\ConstantContactContactListController;

$this->group(
    '/constant-contact', function () {
    $this->get(
        '', ConstantContactAuthorize::class .
          ':getAuthorisationCodeRoute'
    );
    $this->group(
        '/lists', function () {
        $this->get(
            '',
            ConstantContactContactListController::class . ':getExternalRoute'
        );
        $this->group(
            '/{serial}', function () {
            $this->get(
                '',
                ConstantContactContactListController::class . ':getAllRoute'
            );
            $this->put(
                '',
                ConstantContactContactListController::class . ':updateRoute'
            );
            $this->get('/{id}', ConstantContactContactListController::class . ':getSpecificRoute');
            $this->delete(
                '/{id}', ConstantContactContactListController
                       ::class . ':deleteRoute'
            );
        }
        );
    }
    );
}
);