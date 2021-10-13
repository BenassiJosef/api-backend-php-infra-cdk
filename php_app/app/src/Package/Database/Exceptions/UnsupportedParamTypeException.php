<?php

namespace App\Package\Database\Exceptions;

use App\Package\Database\RawStatementExecutor;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class UnsupportedParamTypeException
 * @package App\Package\Database\Exceptions
 */
class UnsupportedParamTypeException extends DatabaseException
{
    /**
     * UnsupportedParamTypeException constructor.
     * @param $param
     * @param string|null $key
     * @throws Exception
     */
    public function __construct($param, ?string $key = null)
    {
        $supportedTypesArr = array_keys(RawStatementExecutor::pdoTypeMapping());
        $supportedTypes    = implode(', ', $supportedTypesArr);
        $type              = gettype($param);
        $extra             = [
            'type'           => $type,
            'supportedTypes' => $supportedTypesArr,
        ];

        if (is_object($param)) {
            $class          = get_class($param);
            $extra['class'] = $class;
            $type           = "${type} (${class})";
        }
        $message = "The type (${type}) is not supported, only (${supportedTypes}) are supported.";
        if ($key !== null) {
            $message .= " This is used by key (${key}).";
        }
        parent::__construct(
            $message,
            StatusCodes::HTTP_INTERNAL_SERVER_ERROR,
            $extra
        );
    }
}