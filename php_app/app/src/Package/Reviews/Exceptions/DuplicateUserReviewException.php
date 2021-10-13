<?php

namespace App\Package\Reviews\Exceptions;

use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class DuplicateUserReviewException extends ReviewException
{

    /**
     * DuplicateUserReviewException constructor.
     * @param string $id
     * @throws Exception
     */
    public function __construct(
        string $id
    ) {
        parent::__construct(
            "Attempting to create duplicate review for id (${id})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}
