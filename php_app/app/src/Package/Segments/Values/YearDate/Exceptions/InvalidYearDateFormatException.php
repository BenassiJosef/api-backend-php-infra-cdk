<?php

namespace App\Package\Segments\Values\YearDate\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidYearDateFormatException
 * @package App\Package\Segments\Values\YearDate\Exceptions
 */
class InvalidYearDateFormatException extends BaseException
{
    /**
     * InvalidYearDateFormatException constructor.
     * @param string $format
     */
    public function __construct(string $format)
    {
        parent::__construct(
            "(${format}) is not a valid format for a YearDate",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}