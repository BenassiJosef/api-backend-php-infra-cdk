<?php

namespace App\Package\Billing\Exceptions;

use App\Package\Billing\Exceptions\BillingException;

use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class SMSTransactionNotFoundException extends BillingException
{

    /**
     * SMSTransactionNotFoundException constructor.
     * @param string $id
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            "SMS transaction not found",
            StatusCodes::HTTP_NOT_FOUND
        );
    }
}
