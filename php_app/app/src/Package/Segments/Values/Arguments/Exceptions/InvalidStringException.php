<?php

namespace App\Package\Segments\Values\Arguments\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidStringException
 * @package App\Package\Segments\Values\Arguments\Exceptions
 */
class InvalidStringException extends BaseException
{
    /**
     * InvalidStringException constructor.
     * @param string $key
     * @param $rawValue
     */
    public function __construct(string $key, $rawValue)
    {
        $type = gettype($rawValue);
        parent::__construct(
            "(${type}) is not a string, please pass a string for querying (${key})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}