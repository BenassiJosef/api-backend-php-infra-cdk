<?php

/**
 * Created by jamieaitken on 01/02/2019 at 12:10
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\ChargeBee;

use Doctrine\ORM\EntityManager;

class ChargeBeeHostedPageController
{
    protected $errorHandler;


    public function __construct()
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
    }

    public function createNewSubscription(array $body)
    {
        $newSubscription = function ($body) {
            return \ChargeBee_HostedPage::checkoutNew($body)->hostedPage()->getValues();
        };

        return $this->errorHandler->handleErrors($newSubscription, $body);
    }

    public function updateExistingSubscription(array $body)
    {
        $update = function ($body) {
            return \ChargeBee_HostedPage::checkoutExisting($body)->hostedPage()->getValues();
        };

        return $this->errorHandler->handleErrors($update, $body);
    }

    public function createPortalSession(array $body)
    {
        $create = function ($body) {
            return \ChargeBee_PortalSession::create($body)->portalSession()->getValues();
        };

        return $this->errorHandler->handleErrors($create, $body);
    }
}
