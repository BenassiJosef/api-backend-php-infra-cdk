<?php

namespace App\Package\Auth\Tokens\Exceptions;

use App\Package\Auth\Exceptions\AuthException;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidProfileIdException
 * @package App\Package\Auth\Tokens\Exceptions
 */
class InvalidProfileIdException extends AuthException
{
    /**
     * InvalidProfileIdException constructor.
     * @param int $profileId
     * @throws Exception
     */
    public function __construct(int $profileId)
    {
        parent::__construct(
            "A profile for this token cannot be found",
            StatusCode::HTTP_UNAUTHORIZED
        );
    }
}