<?php


namespace App\Package\Profile\Data\Exceptions;


use App\Package\Profile\Data\Deletable;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

class DeletionException extends DataException
{
    public function __construct(Deletable $deletable, Throwable $previous)
    {
        $name    = $deletable->name();
        $message = $previous->getMessage();
        parent::__construct(
            "Got exception deleting (${name}) with message (${message})",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [],
            $previous
        );
    }
}