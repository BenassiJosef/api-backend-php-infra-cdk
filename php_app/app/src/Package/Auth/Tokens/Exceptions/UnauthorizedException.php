<?php

namespace App\Package\Auth\Tokens\Exceptions;

use App\Package\Auth\Exceptions\AuthException;
use App\Package\Exceptions\BaseException;
use Exception;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class UnauthorizedException
 * @package App\Package\Auth\Tokens\Exceptions
 */
class UnauthorizedException extends AuthException
{
    /**
     * UnauthorizedException constructor.
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            "The access token you have provided is either invalid, expired, or missing.",
            StatusCode::HTTP_UNAUTHORIZED,
            [],
            $previous
        );
    }
}