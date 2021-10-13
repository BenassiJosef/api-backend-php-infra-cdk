<?php

namespace App\Package\Segments\Values\YearDate\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidWeekStartDayException
 * @package App\Package\Segments\Values\YearDate\Exceptions
 */
class InvalidWeekStartDayException extends BaseException
{
    /**
     * InvalidWeekStartDayException constructor.
     * @param string $startDay
     */
    public function __construct(string $startDay)
    {
        $validWeekStarts = from(YearDateRangeFactory::$weekStartOffsets)
            ->toKeys()
            ->aggregate(
                function (string $aggregate, string $key): string {
                    return "${aggregate}, ${key}";
                }
            );
        parent::__construct(
            "(${startDay}) is not a valid week start day only (${validWeekStarts}) are",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}