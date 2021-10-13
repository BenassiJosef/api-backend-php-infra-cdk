<?php

namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Fields\FieldList;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonModeException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\ValueFactory;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorForTypeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidModifierException;

/**
 * Class LikeComparisonMaker
 * @package App\Package\Segments\Operators\Comparisons
 */
class LikeComparisonMaker implements ComparisonMaker
{

    /**
     * @var ValueFactory $valueFactory
     */
    private $valueFactory;

    /**
     * @var FieldList $fieldList
     */
    private $fieldList;

    /**
     * StandardComparisonMaker constructor.
     * @param ValueFactory | null $valueFactory
     * @param FieldList | null $fieldList
     * @throws InvalidTypeException
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     */
    public function __construct(
        ?ValueFactory $valueFactory = null,
        ?FieldList $fieldList = null
    ) {
        if ($valueFactory === null) {
            $valueFactory = new ValueFactory();
        }
        if ($fieldList === null) {
            $fieldList = FieldList::default();
        }
        $this->valueFactory = $valueFactory;
        $this->fieldList    = $fieldList;
    }

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
     */
    public function make(ComparisonInput $input): Comparison
    {
        $modifier = $input->getComparisonMode();
        if ($modifier === null) {
            throw new InvalidComparisonModeException(
                $input->getComparison(),
                array_keys(LikeComparison::$allowedModifiers)
            );
        }
        $field = $this
            ->fieldList
            ->getField($input->getFieldName());
        return new LikeComparison(
            $field,
            $input->getComparison(),
            $modifier,
            $this->valueFactory->makeValue($field, $input->getValue())
        );
    }

    /**
     * @inheritDoc
     */
    public function supportedTypes(): array
    {
        return [
            Field::TYPE_STRING,
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportedOperatorsForType(string $type): array
    {
        if ($type !== Field::TYPE_STRING) {
            return [];
        }
        return [
            LikeComparison::LIKE,
            LikeComparison::NOT_LIKE,
        ];
    }

    /**
     * @return string[]
     */
    public function supportedModifiers(): array
    {
        return array_keys(LikeComparison::$allowedModifiers);
    }
}