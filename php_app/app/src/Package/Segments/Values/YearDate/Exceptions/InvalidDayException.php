<?php


namespace App\Package\Segments\Values\YearDate\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Values\YearDate\YearDate;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidDayException
 * @package App\Package\Segments\Values\YearDate\Exceptions
 */
class InvalidDayException extends BaseException
{
    /**
     * InvalidDayException constructor.
     * @param string $month
     * @param int $day
     * @throws InvalidMonthException
     */
    public function __construct(string $month, int $day)
    {
        YearDate::validateMonth($month);
        $maxDay = YearDate::$maxMonthDay[$month];
        parent::__construct(
            "(${day}) is not a valid day for month (${month}) only the days (1-${maxDay}) are",
            StatusCodes::HTTP_BAD_REQUEST,
        );
    }
}