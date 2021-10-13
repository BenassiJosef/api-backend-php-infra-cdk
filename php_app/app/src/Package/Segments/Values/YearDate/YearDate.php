<?php

namespace App\Package\Segments\Values\YearDate;

use App\Package\Segments\Values\Arguments\Argument;
use App\Package\Segments\Values\Arguments\ArgumentValue;
use App\Package\Segments\Values\Value;
use App\Package\Segments\Values\ValueFormatter;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use JsonSerializable;

/**
 * Class YearDate
 * @package App\Package\Segments\Values\YearDate
 */
final class YearDate implements JsonSerializable, Value, ValueFormatter
{
    const FORMAT = 'M/j';

    const MONTH_JANUARY   = 'Jan';
    const MONTH_FEBRUARY  = 'Feb';
    const MONTH_MARCH     = 'Mar';
    const MONTH_APRIL     = 'Apr';
    const MONTH_MAY       = 'May';
    const MONTH_JUNE      = 'Jun';
    const MONTH_JULY      = 'Jul';
    const MONTH_AUGUST    = 'Aug';
    const MONTH_SEPTEMBER = 'Sep';
    const MONTH_OCTOBER   = 'Oct';
    const MONTH_NOVEMBER  = 'Nov';
    const MONTH_DECEMBER  = 'Dec';

    /**
     * @var int[]
     */
    public static $maxMonthDay = [
        self::MONTH_JANUARY   => 31,
        self::MONTH_FEBRUARY  => 29,
        self::MONTH_MARCH     => 31,
        self::MONTH_APRIL     => 30,
        self::MONTH_MAY       => 31,
        self::MONTH_JUNE      => 30,
        self::MONTH_JULY      => 31,
        self::MONTH_AUGUST    => 31,
        self::MONTH_SEPTEMBER => 30,
        self::MONTH_OCTOBER   => 31,
        self::MONTH_NOVEMBER  => 30,
        self::MONTH_DECEMBER  => 31,
    ];

    /**
     * @var int[]
     */
    public static $months = [
        self::MONTH_JANUARY   => 1,
        self::MONTH_FEBRUARY  => 2,
        self::MONTH_MARCH     => 3,
        self::MONTH_APRIL     => 4,
        self::MONTH_MAY       => 5,
        self::MONTH_JUNE      => 6,
        self::MONTH_JULY      => 7,
        self::MONTH_AUGUST    => 8,
        self::MONTH_SEPTEMBER => 9,
        self::MONTH_OCTOBER   => 10,
        self::MONTH_NOVEMBER  => 11,
        self::MONTH_DECEMBER  => 12,
    ];

    private static $arrayKeys = [
        'day',
        'month'
    ];

    /**
     * @param array $data
     * @param array $aliasMap
     * @return static
     * @throws InvalidDayException
     * @throws InvalidMonthException
     */
    public static function fromArray(array $data, array $aliasMap = []): ?self
    {
        $arrayData = [];
        foreach (self::$arrayKeys as $arrayKey) {
            if (array_key_exists($arrayKey, $data)) {
                $arrayData[$arrayKey] = $data[$arrayKey];
                continue;
            }
            if (array_key_exists($arrayKey, $aliasMap)) {
                $aliasedKey           = $aliasMap[$arrayKey];
                $arrayData[$arrayKey] = $data[$aliasedKey];
            }
        }

        [
            'day'   => $day,
            'month' => $monthInt
        ] = $arrayData;

        if ($day === null || $monthInt === null) {
            return null;
        }

        $monthMap = array_flip(self::$months);
        return new self(
            json_encode($arrayData),
            $monthMap[$monthInt],
            $day
        );
    }

    /**
     * @param string $format
     * @return static
     * @throws InvalidYearDateFormatException
     * @throws InvalidDayException
     * @throws InvalidMonthException
     */
    public static function fromString(string $format): self
    {
        $formatString = string($format);
        if (!$formatString->contains('/')) {
            throw new InvalidYearDateFormatException($format);
        }
        [$month, $dayString] = $formatString->explode('/', 2);
        if (!is_numeric($dayString)) {
            throw new InvalidYearDateFormatException($format);
        }
        $day = intval($dayString);
        return new self(
            $format,
            $month,
            $day
        );
    }

    /**
     * @param string $month
     * @throws InvalidMonthException
     */
    public static function validateMonth(string $month): void
    {
        if (!array_key_exists($month, self::$months)) {
            throw new InvalidMonthException($month);
        }
    }

    /**
     * @param string $month
     * @return string
     */
    private static function normaliseMonth(string $month): string
    {
        $month = strtolower($month);
        $month = ucfirst($month);
        return $month;
    }

    /**
     * @param string $month
     * @param int $day
     * @throws InvalidDayException
     * @throws InvalidMonthException
     */
    public static function validateMonthDay(string $month, int $day): void
    {
        self::validateMonth($month);
        $maxDay = self::$maxMonthDay[$month];
        if ($day < 1 || $day > $maxDay) {
            throw new InvalidDayException($month, $day);
        }
    }

    /**
     * @var string $rawValue
     */
    private $rawValue;

    /**
     * @var string $month
     */
    private $month;

    /**
     * @var int $day
     */
    private $day;

    /**
     * YearDate constructor.
     * @param string $rawValue
     * @param string $month
     * @param int $day
     * @throws InvalidDayException
     * @throws InvalidMonthException
     */
    public function __construct(
        string $rawValue,
        string $month,
        int $day
    ) {
        $month          = self::normaliseMonth($month);
        $this->rawValue = $rawValue;
        self::validateMonthDay($month, $day);
        $this->month = $month;
        $this->day   = $day;
    }

    /**
     * @return string
     */
    public function getMonth(): string
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getMonthNumber(): int
    {
        return self::$months[$this->month];
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return $this->rawValue;
    }

    /**
     * @return string | int | bool
     */
    public function rawValue()
    {
        return $this->rawValue;
    }

    /**
     * @return string | int | boolean | null
     */
    public function format()
    {
        $month = ucfirst($this->month);
        $day   = $this->day;
        return "${month}/${day}";
    }


    /**
     * @return Argument[]
     */
    public function arguments(): array
    {
        return [
            new ArgumentValue('month', $this->getMonthNumber()),
            new ArgumentValue('day', $this->getDay())
        ];
    }
}