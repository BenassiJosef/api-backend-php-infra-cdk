<?php

namespace App\Package\Segments\Values\YearDate;

use DateTimeImmutable;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidWeekStartDayException;

/**
 * Class YearDateRangeFactory
 * @package App\Package\Segments\Values\YearDate
 */
class YearDateRangeFactory
{
    const WEEK_START_MONDAY = 'monday';
    const WEEK_START_SUNDAY = 'sunday';

    /**
     * @var int[] $weekStartOffsets
     */
    public static $weekStartOffsets = [
        self::WEEK_START_SUNDAY => 0,
        self::WEEK_START_MONDAY => 1,
    ];

    /**
     * @var string $weekStart
     */
    private $weekStart;

    /**
     * @var DateTimeImmutable $today
     */
    private $today;

    /**
     * @var YearDateMulti[] $specialRanges
     */
    private $specialRanges = [];

    /**
     * YearDateRangeFactory constructor.
     * @param string $weekStart
     * @param DateTimeImmutable | null $today
     * @throws InvalidWeekStartDayException
     */
    public function __construct(
        string             $weekStart = self::WEEK_START_MONDAY,
        ?DateTimeImmutable $today = null
    ) {
        self::validateWeekStart($weekStart);
        if ($today === null) {
            $today = new DateTimeImmutable();
        }
        $this->weekStart = $weekStart;
        $this->today     = $today;
    }

    /**
     * @param string $rangeDefinition
     * @return YearDateMulti
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     */
    public function fromString(string $rangeDefinition): YearDateMulti
    {
        $specialRanges = $this->specialRanges();
        if (array_key_exists($rangeDefinition, $specialRanges)) {
            return $specialRanges[$rangeDefinition];
        }
        return $this->parseRangeDefinition($rangeDefinition);
    }

    /**
     * @param string $rangeDefinition
     * @return YearDateMulti
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     */
    private function parseRangeDefinition(string $rangeDefinition): YearDateMulti
    {
        $rangeDefinitionString = string($rangeDefinition);
        if (!$rangeDefinitionString->contains('-')) {
            return new YearDateMulti(
                $rangeDefinition,
                YearDate::fromString($rangeDefinition)
            );
        }
        [$startDefinition, $endDefinition] = $rangeDefinitionString->explode('-', 2);
        return new YearDateMulti(
            $rangeDefinition,
            YearDate::fromString($startDefinition),
            YearDate::fromString($endDefinition)
        );
    }

    /**
     * @return YearDateMulti[]
     */
    public function specialRanges(): array
    {
        if (count($this->specialRanges) > 0) {
            return $this->specialRanges;
        }
        $periodRangeDefinitions = [
            'day'   => [
                YearDateMulti::YESTERDAY => '-1 day',
                YearDateMulti::TODAY     => null,
                YearDateMulti::TOMORROW  => '+1 day',
            ],
            'week'  => [
                YearDateMulti::LAST_WEEK => '-1 week',
                YearDateMulti::THIS_WEEK => null,
                YearDateMulti::NEXT_WEEK => '+1 week',
            ],
            'month' => [
                YearDateMulti::LAST_MONTH => '-1 month',
                YearDateMulti::THIS_MONTH => null,
                YearDateMulti::NEXT_MONTH => '+1 month',
            ],
        ];
        foreach ($periodRangeDefinitions as $period => $rangeDefinitions) {
            foreach ($rangeDefinitions as $rangeKey => $rangeDefinition) {
                $this->specialRanges[$rangeKey] = $this->$period($rangeKey, $rangeDefinition);
            }
        }
        return $this->specialRanges;
    }

    /**
     * @param string $rawValue
     * @param string|null $dateTimeString
     * @return YearDateMulti
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     */
    private function day(string $rawValue, ?string $dateTimeString = null): YearDateMulti
    {
        if ($dateTimeString === null) {
            return new YearDateMulti(
                $rawValue,
                YearDate::fromString($this->today->format(YearDate::FORMAT))
            );
        }
        return new YearDateMulti(
            $rawValue,
            YearDate::fromString($this->formattedDate($this->today, $dateTimeString))
        );
    }

    /**
     * @param string $rawValue
     * @param string|null $dateTimeString
     * @return YearDateMulti
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     */
    private function week(string $rawValue, ?string $dateTimeString = null): YearDateMulti
    {
        $dateTime = $this->today;
        if ($dateTimeString !== null) {
            $dateTime = $this->today->modify($dateTimeString);
        }
        $weekDay         = intval($dateTime->format('w'));
        $offset          = self::$weekStartOffsets[$this->weekStart];
        $weekStartOffset = $weekDay - $offset;
        $weekStartDate   = $dateTime->modify("-${weekStartOffset} days");
        $yearDates       = [];
        for ($i = 0; $i < 7; $i++) {
            $formattedDate = $weekStartDate->format(YearDate::FORMAT);
            $yearDates[]   = YearDate::fromString($formattedDate);
            $weekStartDate = $weekStartDate->modify("+1 day");
        }
        return new YearDateMulti(
               $rawValue,
            ...$yearDates
        );
    }

    /**
     * @param DateTimeImmutable $dateTime
     * @param string $dateTimeString
     * @return string
     */
    private function formattedDate(DateTimeImmutable $dateTime, string $dateTimeString): string
    {
        return $dateTime
            ->modify($dateTimeString)
            ->format(YearDate::FORMAT);
    }

    /**
     * @param string $rawValue
     * @param string|null $dateTimeString
     * @return YearDateMulti
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     */
    private function month(string $rawValue, ?string $dateTimeString = null): YearDateMulti
    {
        $referenceDate = $this->today;
        if ($dateTimeString !== null) {
            $referenceDate = $this->today->modify($dateTimeString);
        }
        $currentDay    = intval($referenceDate->format('j'));
        $daysFromStart = $currentDay - 1;
        $monthStart    = $referenceDate->modify("-${daysFromStart} days");
        $lastDay       = intval($monthStart->format('t'));
        $yearDates     = [];
        $dateTime      = $monthStart;
        for ($i = 1; $i <= $lastDay; $i++) {
            $yearDates[] = YearDate::fromString(
                $dateTime->format(YearDate::FORMAT)
            );
            $dateTime    = $dateTime->modify("+1 day");
        }
        return new YearDateMulti(
               $rawValue,
            ...$yearDates
        );
    }

    /**
     * @param string $weekStart
     * @throws InvalidWeekStartDayException
     */
    public static function validateWeekStart(string $weekStart): void
    {
        if (!array_key_exists($weekStart, self::$weekStartOffsets)) {
            throw new InvalidWeekStartDayException($weekStart);
        }
    }

}