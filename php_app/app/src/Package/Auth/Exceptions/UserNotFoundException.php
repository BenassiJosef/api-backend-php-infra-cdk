<?php

namespace App\Package\Auth\Exceptions;

use App\Package\Exceptions\BaseException;
use Slim\Http\StatusCode;

/**
 * Class UserNotFoundException
 * @package App\Package\Auth\Exceptions
 */
class UserNotFoundException extends BaseException
{
    /**
     * UserNotFoundException constructor.
     * @param string $userId
     * @throws \Exception
     */
    public function __construct(string $userId)
    {
        parent::__construct(
            "A user with the id (${userId}) cannot be found",
            StatusCode::HTTP_NOT_FOUND,
            [
                'userId' => $userId
            ]
        );
    }
}