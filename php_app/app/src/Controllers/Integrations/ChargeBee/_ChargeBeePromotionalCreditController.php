<?php
/**
 * Created by jamieaitken on 31/01/2018 at 10:32
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\ChargeBee;


class _ChargeBeePromotionalCreditController
{

    protected $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
    }

    public function addPromotionalCredits(array $body)
    {
        $newCredits = function ($body) {
            return \ChargeBee_PromotionalCredit::add([
                'customerId'  => $body['uid'],
                'amount'      => $body['amount'],
                'description' => $body['description']
            ])->promotionalCredit()->getValues();
        };

        return $this->errorHandler->handleErrors($newCredits, $body);
    }
}