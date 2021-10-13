<?php


namespace App\Package\Auth\Tokens\Exceptions;

use App\Package\Auth\Exceptions\AuthException;

/**
 * Class InvalidUserIDException
 * @package App\Package\Auth\Exceptions
 */
class InvalidUserIDException extends AuthException
{
    public function __construct(string $userId)
    {
        parent::__construct("A user with the ID (${userId}) cannot be found");
    }
}