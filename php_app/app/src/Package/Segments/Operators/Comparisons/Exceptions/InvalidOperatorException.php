<?php


namespace App\Package\Segments\Operators\Comparisons\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

class InvalidOperatorException extends BaseException
{
    public function __construct(string $operator, ?string $type = null)
    {
        $message = "(${operator}) is not a valid operator";
        if ($type !== null) {
            $message .= " for type (${type})";
        }
        parent::__construct(
            $message,
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}