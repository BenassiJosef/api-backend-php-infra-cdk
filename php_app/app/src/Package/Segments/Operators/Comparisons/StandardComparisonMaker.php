<?php


namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\FieldList;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\ValueFactory;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;

/**
 * Class StandardComparisonMaker
 * @package App\Package\Segments\Operators\Comparisons
 */
class StandardComparisonMaker implements ComparisonMaker
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
     * @throws InvalidOperatorException
     * @throws FieldNotFoundException
     * @throws InvalidBooleanException
     * @throws InvalidDateTimeException
     * @throws InvalidDayException
     * @throws InvalidIntegerException
     * @throws InvalidMonthException
     * @throws InvalidStringException
     * @throws InvalidYearDateFormatException
     * @throws InvalidTypeException
     */
    public function make(ComparisonInput $input): Comparison
    {
        $field = $this->fieldList->getField($input->getFieldName());
        return new StandardComparison(
            $field,
            $input->getComparison(),
            $this->valueFactory->makeValue($field, $input->getValue())
        );
    }

    /**
     * @inheritDoc
     */
    public function supportedTypes(): array
    {
        return array_keys(StandardComparison::$allowedOperatorsForType);
    }

    /**
     * @inheritDoc
     */
    public function supportedOperatorsForType(string $type): array
    {
        if (!array_key_exists($type, StandardComparison::$allowedOperatorsForType)) {
            return [];
        }
        return array_keys(StandardComparison::$allowedOperatorsForType[$type]);
    }

    /**
     * @return string[]
     */
    public function supportedModifiers(): array
    {
        return [];
    }
}
