<?php


namespace App\Package\Auth\Scopes\Exceptions;

use App\Package\Auth\Scopes\Scope;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidScopeTypeException
 * @package App\Package\Auth\Exceptions
 */
class InvalidTypeException extends ScopeException
{
    /**
     * InvalidScopeTypeException constructor.
     * @param string $type
     * @throws Exception
     */
    public function __construct(string $type)
    {
        $allowedTypes = implode(', ', Scope::allowedTypes());
        parent::__construct(
            "(${type}) is not a permitted type of scope, only (${allowedTypes}) are permitted",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'allowedTypes' => Scope::allowedTypes(),
            ]
        );
    }
}