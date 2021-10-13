<?php


namespace App\Package\Config\Exceptions;


use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

class FailedToLoadEnvironmentVarException extends ConfigException
{
    public function __construct(string $variableName)
    {
        parent::__construct(
            "Failed to load environment variable with name (${variableName})",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}