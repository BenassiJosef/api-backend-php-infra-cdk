<?php

namespace App\Package\Segments\Values\YearDate\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Values\YearDate\YearDate;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidMonthException
 * @package App\Package\Segments\Values\YearDate\Exceptions
 */
class InvalidMonthException extends BaseException
{
    /**
     * InvalidMonthException constructor.
     * @param string $month
     */
    public function __construct(string $month)
    {
        $validMonths = from(YearDate::$months)
            ->toKeys()
            ->aggregate(
                function (string $aggregate, string $key): string {
                    return "${aggregate}, ${key}";
                }
            );
        parent::__construct(
            "(${month}) is not a valid month only (${validMonths}) are valid",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}