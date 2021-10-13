<?php

namespace App\Package\Auth\ExternalServices\Exceptions;

use App\Package\Auth\Exceptions\AuthException;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class MissingKeyException
 * @package App\Package\Auth\ExternalServices\Exceptions
 */
class MissingKeyException extends AuthException
{
    /**
     * MissingKeyException constructor.
     * @param string $key
     * @param string[] $requiredKeys
     * @throws Exception
     */
    public function __construct(string $key, array $requiredKeys = [])
    {
        $requiredKeysString = implode(', ', $requiredKeys);
        parent::__construct(
            "The key (${key}) is missing (${requiredKeysString}) are required keys",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'key'          => $key,
                'requiredKeys' => $requiredKeys,
            ]
        );
    }
}