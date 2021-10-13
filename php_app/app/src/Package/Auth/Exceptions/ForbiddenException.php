<?php

namespace App\Package\Auth\Exceptions;

use Exception;
use Slim\Http\StatusCode;

/**
 * Class ForbiddenException
 * @package App\Package\Auth\Exceptions
 */
class ForbiddenException extends AuthException
{
    /**
     * ForbiddenException constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            "You cannot access this resource with your token, please check you have the correct role/access/scope",
            StatusCode::HTTP_FORBIDDEN
        );
    }
}