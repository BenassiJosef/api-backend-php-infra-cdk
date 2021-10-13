<?php

namespace App\Package\Segments\Database\BaseQueries\Exceptions;

use App\Package\Exceptions\BaseException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class UnknownBaseQueryException
 * @package App\Package\Segments\Database\BaseQueries\Exceptions
 */
class UnknownBaseQueryException extends BaseException
{
    /**
     * UnknownBaseQueryException constructor.
     * @param string $baseQueryType
     * @param string[] $knownTypes
     */
    public function __construct(string $baseQueryType, array $knownTypes)
    {
        $knownTypesString = implode(', ', $knownTypes);
        parent::__construct(
            "(${baseQueryType}) is not supported as a query type, only (${knownTypesString}) are supported",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}