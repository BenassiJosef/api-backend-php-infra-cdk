<?php

namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonModeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidModifierException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorForTypeException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;

/**
 * Interface ComparisonMaker
 * @package App\Package\Segments\Operators\Comparisons
 */
interface ComparisonMaker
{
    /**
     * @param ComparisonInput $input
     * @return Comparison
     * @throws InvalidModifierException
     * @throws InvalidOperatorException
     * @throws InvalidOperatorForTypeException
     * @throws InvalidComparisonModeException
     * @throws FieldNotFoundException
     * @throws InvalidBooleanException
     * @throws InvalidIntegerException
     * @throws InvalidStringException
     * @throws InvalidDateTimeException
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidYearDateFormatException
     * @throws InvalidTypeException
     */
    public function make(ComparisonInput $input): Comparison;

    /**
     * @return string[]
     */
    public function supportedTypes(): array;

    /**
     * @param string $type
     * @return string[]
     */
    public function supportedOperatorsForType(string $type): array;

    /**
     * @return string[]
     */
    public function supportedModifiers(): array;

}