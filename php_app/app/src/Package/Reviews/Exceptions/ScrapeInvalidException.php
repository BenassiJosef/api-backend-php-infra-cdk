<?php

namespace App\Package\Reviews\Exceptions;

use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class ScrapeInvalidException extends ReviewException
{

    /**
     * ReviewSettingsNotFoundException constructor.
     * @param string $id
     * @param string $reason
     * @throws Exception
     */
    public function __construct(
        string $id,
        string $reason

    ) {
        parent::__construct(
            "Scrape for id (${id}) is invalid, gave the following reason for failure ${reason}",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}
