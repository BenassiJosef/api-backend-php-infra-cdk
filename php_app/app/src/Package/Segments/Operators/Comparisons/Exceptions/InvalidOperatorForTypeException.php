<?php


namespace App\Package\Segments\Operators\Comparisons\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidOperatorForTypeException
 * @package App\Package\Segments\Operators\Comparisons\Exceptions
 */
class InvalidOperatorForTypeException extends BaseException
{
    public function __construct(string $type, string $operator)
    {
        parent::__construct(
            "(${operator}) is not valid for fields of type (${type})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}