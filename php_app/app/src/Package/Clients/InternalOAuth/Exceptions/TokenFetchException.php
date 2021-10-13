<?php

namespace App\Package\Clients\InternalOAuth\Exceptions;

use Slim\Http\StatusCode;
use Throwable;

/**
 * Class TokenFetchException
 * @package App\Package\Clients\InternalOAuth\Exceptions
 */
class TokenFetchException extends OAuthException
{
    /**
     * TokenFetchException constructor.
     * @param Throwable|null $previous
     * @throws \Exception
     */
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(
            "Failed to fetch oauth token",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [],
            $previous
        );
    }
}