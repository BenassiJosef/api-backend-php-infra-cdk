<?php

namespace App\Package\Billing\Exceptions;

use App\Package\Billing\Exceptions\BillingException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class SMSTransactionInsufficientBalance extends BillingException
{

    /**
     * SMSTransactionInsufficientBalance constructor.
     * @param string $id
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            "SMS balance insufficient",
            StatusCodes::HTTP_PAYMENT_REQUIRED
        );
    }
}
