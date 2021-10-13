<?php

namespace App\Package\Reports;

class Time
{

    /**
     * @var int $year
     */
    private $year;
    /**
     * @var int $month
     */
    private $month;
    /**
     * @var int $day
     */
    private $day;
    /**
     * @var int $dayOfWeek
     */
    private $dayOfWeek;
    /**
     * @var int $hour
     */
    private $hour;

    /**
     * Time constructor.
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $dayOfWeek
     * @param int $hour
     */
    public function __construct(
        int $year,
        int $month,
        int $day,
        int $dayOfWeek,
        int $hour
    ) {
        $this->year           = $year;
        $this->month    = $month;
        $this->day    = $day;
        $this->dayOfWeek    = $dayOfWeek;
        $this->hour    = $hour;
    }


    public function getYearMonth()
    {
        return $this->year . '-' . $this->month;
    }
    public function getDate()
    {
        return $this->year . '-' . $this->month . '-' . $this->day;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'year'                      => $this->year,
            'month'                      => $this->month,
            'day_of_week'                      => $this->dayOfWeek,
            'day'                      => $this->day,
            'hour'                      => $this->hour,
        ];
    }
}
