<?php

namespace App\Package\Location\Exceptions;

use Exception;
use Slim\Http\StatusCode;

/**
 * Class LocationNotFoundException
 * @package App\Package\Location\Exceptions
 */
class LocationNotFoundException extends LocationException
{
    /**
     * LocationNotFoundException constructor.
     * @param string $serial
     * @throws Exception
     */
    public function __construct(string $serial)
    {
        parent::__construct(
            "A location with the serial (${serial}) cannot be found.",
            StatusCode::HTTP_NOT_FOUND,
            [
                'serial' => $serial,
            ]
        );
    }
}