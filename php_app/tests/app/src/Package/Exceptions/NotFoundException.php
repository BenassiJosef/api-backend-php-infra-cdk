<?php


namespace StampedeTests\app\src\Package\Exceptions;


use App\Package\Exceptions\BaseException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class NotFoundException extends BaseException
{
    public function __construct(
        string $message = "An error has occured",
        int $code = StatusCodes::HTTP_NOT_FOUND,
        array $extra = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $extra, $previous);
    }
}