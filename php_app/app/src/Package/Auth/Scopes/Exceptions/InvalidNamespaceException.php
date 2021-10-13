<?php


namespace App\Package\Auth\Scopes\Exceptions;


use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;

/**
 * Class InvalidNamespaceException
 * @package App\Package\Auth\Scopes\Exceptions
 */
class InvalidNamespaceException extends ScopeException
{
    /**
     * InvalidNamespaceException constructor.
     * @param array $namespace
     * @throws Exception
     */
    public function __construct(array $namespace)
    {
        $formattedNamespace = implode(':', $namespace);
        parent::__construct(
            "All components of a namespace must be a string (${formattedNamespace})",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [
                'namespace' => $namespace
            ]
        );
    }
}