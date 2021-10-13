<?php


namespace App\Package\Auth\ExternalServices\Exceptions;

use App\Package\Auth\Exceptions\AuthException;
use Slim\Http\StatusCode;

/**
 * Class InvalidParameterException
 * @package App\Package\Auth\ExternalServices\Exceptions
 */
class InvalidParameterException extends AuthException
{
    /**
     * InvalidParameterException constructor.
     * @param string $key
     * @param $parameter
     * @throws \Exception
     */
    public function __construct(string $key, $parameter)
    {
        $type = gettype($parameter);
        parent::__construct(
            "Parameter (${key}) must be a string type (${type}) is not permitted",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'key' => $key
            ]
        );
    }
}