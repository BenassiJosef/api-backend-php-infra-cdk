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
use App\Package\Segments\Values\DateTime\DateTimeFactory;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\ValueFactory;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidWeekStartDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;

/**
 * Class ComparisonFactory
 * @package App\Package\Segments\Operators\Comparisons
 */
class ComparisonFactory
{
    /**
     * @param ValueFactory $valueFactory
     * @param FieldList|null $fieldList
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidTypeException
     */
    public static function fromValueFactory(
        ValueFactory $valueFactory,
        ?FieldList $fieldList = null
    ): self {
        if ($fieldList === null) {
            $fieldList = FieldList::default();
        }
        return new self(
            $fieldList,
            new StandardComparisonMaker(
                $valueFactory,
                $fieldList
            ),
            new LikeComparisonMaker(
                $valueFactory,
                $fieldList
            )
        );
    }

    /**
     * @param string $weekStart
     * @param string $format
     * @param FieldList|null $fieldList
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidTypeException
     * @throws InvalidWeekStartDayException
     */
    public static function configure(
        string $weekStart = YearDateRangeFactory::WEEK_START_MONDAY,
        string $format = DateTimeFactory::INPUT_FORMAT,
        FieldList $fieldList = null
    ): self {
        return self::fromValueFactory(
            ValueFactory::configure($weekStart, $format),
            $fieldList
        );
    }

    /**
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidTypeException
     */
    public static function default(): self
    {
        return new self(
            FieldList::default(),
            new StandardComparisonMaker(),
            new LikeComparisonMaker()
        );
    }

    /**
     * @var FieldList $fieldList
     */
    private $fieldList;

    /**
     * @var ComparisonMaker[][] $makerMap
     */
    private $makerMap = [];

    /**
     * ComparisonFactory constructor.
     * @param FieldList $fieldList
     * @param ComparisonMaker[] $comparisonMakers
     */
    public function __construct(FieldList $fieldList, ComparisonMaker ...$comparisonMakers)
    {
        $this->fieldList = $fieldList;
        foreach ($comparisonMakers as $comparisonMaker) {
            $this->register($comparisonMaker);
        }
    }

    /**
     * @param ComparisonMaker $comparisonMaker
     * @return $this
     */
    public function register(ComparisonMaker $comparisonMaker): self
    {
        $typeMap        = [];
        $supportedTypes = $comparisonMaker->supportedTypes();
        foreach ($supportedTypes as $supportedType) {
            $operatorMap        = [];
            $supportedOperators = $comparisonMaker->supportedOperatorsForType($supportedType);
            foreach ($supportedOperators as $supportedOperator) {
                $operatorMap[$supportedOperator] = $comparisonMaker;
            }
            $typeMap[$supportedType] = $operatorMap;
        }
        $this->makerMap = array_merge_recursive($this->makerMap, $typeMap);
        return $this;
    }

    /**
     * @param ComparisonInput $input
     * @return Comparison
     * @throws Exceptions\InvalidComparisonModeException
     * @throws Exceptions\InvalidModifierException
     * @throws Exceptions\InvalidOperatorForTypeException
     * @throws FieldNotFoundException
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
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
        return $this->makerForInput($input)->make($input);
    }

    /**
     * @param ComparisonInput $input
     * @return ComparisonMaker
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
     * @throws FieldNotFoundException
     */
    private function makerForInput(ComparisonInput $input): ComparisonMaker
    {
        $type = $this
            ->fieldList
            ->getField($input->getFieldName())
            ->getType();
        return $this->makerForTypeAndOperator($type, $input->getComparison());
    }

    /**
     * @param string $type
     * @param string $operator
     * @return ComparisonMaker
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
     */
    private function makerForTypeAndOperator(string $type, string $operator): ComparisonMaker
    {
        if (!array_key_exists($type, $this->makerMap)) {
            throw new InvalidTypeException($type, array_keys($this->makerMap));
        }
        $makersByOperator = $this->makerMap[$type];
        if (!array_key_exists($operator, $makersByOperator)) {
            throw new InvalidOperatorException($operator, $type);
        }
        return $makersByOperator[$operator];
    }

    /**
     * @param string $type
     * @return array
     * @throws InvalidTypeException
     */
    public function allowedOperatorsForType(string $type): array
    {
        if (!array_key_exists($type, $this->makerMap)) {
            throw new InvalidTypeException($type, array_keys($this->makerMap));
        }
        $makersByOperator = $this->makerMap[$type];
        return array_keys($makersByOperator);
    }

    /**
     * @param string $type
     * @param string $operator
     * @return array
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
     */
    public function modifiersForTypeAndOperator(string $type, string $operator): array
    {
        return $this
            ->makerForTypeAndOperator($type, $operator)
            ->supportedModifiers();
    }
}