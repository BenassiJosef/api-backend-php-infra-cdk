<?php

namespace App\Package\Segments\Values\YearDate;

use App\Package\Segments\Values\MultiValue;
use App\Package\Segments\Values\Value;

/**
 * Class YearDateRange
 * @package App\Package\Segments\Values\YearDate
 */
class YearDateMulti implements Value, MultiValue
{
    const YESTERDAY  = 'yesterday';
    const TODAY      = 'today';
    const TOMORROW   = 'tomorrow';
    const LAST_WEEK  = 'last-week';
    const THIS_WEEK  = 'this-week';
    const NEXT_WEEK  = 'next-week';
    const LAST_MONTH = 'last-month';
    const THIS_MONTH = 'this-month';
    const NEXT_MONTH = 'next-month';

    /**
     * @var bool[]
     */
    public static $specialRanges = [
        self::YESTERDAY  => true,
        self::TODAY      => true,
        self::TOMORROW   => true,
        self::LAST_WEEK  => true,
        self::THIS_WEEK  => true,
        self::NEXT_WEEK  => true,
        self::LAST_MONTH => true,
        self::THIS_MONTH => true,
        self::NEXT_MONTH => true,
    ];

    /**
     * @var string $rawValue
     */
    private $rawValue;

    /**
     * @var YearDate[]
     */
    private $yearDates;

    /**
     * YearDateMulti constructor.
     * @param string $rawValue
     * @param YearDate ...$yearDates
     */
    public function __construct(
        string $rawValue,
        YearDate ...$yearDates
    ) {
        $this->rawValue  = $rawValue;
        $this->yearDates = $yearDates;
    }

    /**
     * @return string | int | bool
     */
    public function rawValue()
    {
        return $this->rawValue;
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        return from($this->yearDates)
            ->selectMany(
                function (YearDate $date): array {
                    return $date->arguments();
                }
            )
            ->toArray();
    }

    /**
     * @return Value[]
     */
    public function values(): array
    {
        return from($this->yearDates)
            ->select(
                function (YearDate $date): Value {
                    return $date;
                }
            )
            ->toArray();
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
}