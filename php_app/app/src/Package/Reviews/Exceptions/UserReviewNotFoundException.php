<?php

namespace App\Package\Reviews\Exceptions;

use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class UserReviewNotFoundException extends ReviewException
{

    /**
     * UserReviewNotFoundException constructor.
     * @param string $id
     * @throws Exception
     */
    public function __construct(
        string $id
    ) {
        parent::__construct(
            "User review with id (${id}) not found",
            StatusCodes::HTTP_NOT_FOUND
        );
    }
}
