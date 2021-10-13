<?php

namespace App\Package\Exceptions;

use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class NotImplementedException
 * @package App\Package\Exceptions
 */
class NotImplementedException extends BaseException
{
    public function __construct(
        string $message = "Feature not implemented",
        array $extra = [],
        Throwable $previous = null
    ) {
        parent::__construct($message, StatusCode::HTTP_NOT_IMPLEMENTED, $extra, $previous);
    }
}