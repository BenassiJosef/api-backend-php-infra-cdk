<?php

namespace App\Package\Location\Exceptions;

use Slim\Http\StatusCode;

/**
 * Class NoLocationInRequestException
 * @package App\Package\Location\Exceptions
 */
class NoLocationInRequestException extends LocationException
{
    /**
     * NoLocationInRequestException constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct(
            "There is no serial in the request, in order to use LocationProvider, the route must have a serial in it.",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}