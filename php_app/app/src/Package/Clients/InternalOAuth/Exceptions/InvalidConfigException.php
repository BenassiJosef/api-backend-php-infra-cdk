<?php


namespace App\Package\Clients\InternalOAuth\Exceptions;


use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

class InvalidConfigException extends OAuthException
{
    public function __construct(
        string $variableName
    ) {
        parent::__construct(
            "Could not load config for (${variableName})",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}