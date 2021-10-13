<?php

namespace App\Package\Segments\Values;

use App\Package\Segments\Fields\BaseField;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Values\Arguments\ArgumentValue;
use App\Package\Segments\Values\DateTime\DateTimeFactory;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidWeekStartDayException;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;

/**
 * Class ValueFactory
 * @package App\Package\Segments\Values
 */
class ValueFactory
{
    /**
     * @param string $weekStart
     * @param string $format
     * @return static
     * @throws InvalidWeekStartDayException
     */
    public static function configure(
        string $weekStart = YearDateRangeFactory::WEEK_START_MONDAY,
        string $format = DateTimeFactory::INPUT_FORMAT
    ): self {
        return new self(
            new YearDateRangeFactory(
                $weekStart
            ),
            new DateTimeFactory(
                $format,
            )
        );
    }

    /**
     * @var YearDateRangeFactory $yearDateRangeFactory
     */
    private $yearDateRangeFactory;

    /**
     * @var DateTimeFactory $dateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * ValueFactory constructor.
     * @param YearDateRangeFactory | null $yearDateRangeFactory
     * @param DateTimeFactory | null $dateTimeFactory
     */
    public function __construct(
        ?YearDateRangeFactory $yearDateRangeFactory = null,
        ?DateTimeFactory $dateTimeFactory = null
    ) {
        if ($yearDateRangeFactory === null) {
            $yearDateRangeFactory = new YearDateRangeFactory();
        }
        if ($dateTimeFactory === null) {
            $dateTimeFactory = new DateTimeFactory();
        }
        $this->yearDateRangeFactory = $yearDateRangeFactory;
        $this->dateTimeFactory      = $dateTimeFactory;
    }


    /**
     * @param Field $field
     * @param $rawValue
     * @return Value
     * @throws Arguments\Exceptions\InvalidBooleanException
     * @throws Arguments\Exceptions\InvalidIntegerException
     * @throws Arguments\Exceptions\InvalidStringException
     * @throws YearDate\Exceptions\InvalidDayException
     * @throws YearDate\Exceptions\InvalidMonthException
     * @throws YearDate\Exceptions\InvalidYearDateFormatException
     * @throws DateTime\InvalidDateTimeException
     */
    public function makeValue(Field $field, $rawValue): Value
    {
        switch ($field->getType()) {
            case Field::TYPE_STRING:
                return ArgumentValue::stringValue($field->getKey(), $rawValue);
            case Field::TYPE_INTEGER:
                return ArgumentValue::integerValue($field->getKey(), $rawValue);
            case Field::TYPE_BOOLEAN:
                return ArgumentValue::booleanValue($field->getKey(), $rawValue);
            case Field::TYPE_YEARDATE:
                return $this->yearDateRangeFactory->fromString($rawValue);
            case Field::TYPE_DATETIME:
                return $this->dateTimeFactory->fromString($field->getKey(), $rawValue);
        }
    }

    /**
     * @param string $type
     * @return string[]
     */
    public function specialValuesForType(string $type): array
    {
        if ($type === Field::TYPE_DATETIME) {
            return array_keys($this->dateTimeFactory->specialFormats());
        }
        if ($type === Field::TYPE_YEARDATE) {
            return array_keys($this->yearDateRangeFactory->specialRanges());
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function types(): array
    {
        return array_keys(BaseField::$types);
    }

}