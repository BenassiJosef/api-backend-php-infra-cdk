<?php

namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Operators\Logic\Container;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class UnsupportedNodeTypeException
 * @package App\Package\Segments\Database\Parse\Exceptions
 */
class UnsupportedNodeTypeException extends BaseException
{
    /**
     * UnsupportedNodeTypeException constructor.
     * @param string $type
     * @param string[] $supportedTypes
     */
    public function __construct(string $type, ?array $supportedTypes = null)
    {
        if ($supportedTypes === null) {
            $supportedTypes = array_keys(Container::$allowedTypes);
        }
        $allowedTypesString = implode(', ', $supportedTypes);
        parent::__construct(
            "(${type}) is not a valid type only (${allowedTypesString}) are allowed",
            StatusCodes::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}