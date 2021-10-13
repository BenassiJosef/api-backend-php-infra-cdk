<?php


namespace App\Package\Auth\Scopes\Exceptions;

use App\Package\Auth\Scopes\Scope;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidScopeServiceException
 * @package App\Package\Auth\Exceptions
 */
class InvalidServiceException extends ScopeException
{
    /**
     * InvalidScopeServiceException constructor.
     * @param string $serviceName
     * @throws Exception
     */
    public function __construct(string $serviceName)
    {
        $allowedServices = implode(', ', Scope::allowedServices());
        parent::__construct(
            "(${serviceName}) is not a service that can be used for"
            . " a scope, only the following are (${allowedServices})",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'allowedServices' => Scope::allowedServices(),
            ]
        );
    }
}