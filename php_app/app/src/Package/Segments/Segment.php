<?php

namespace App\Package\Segments;

use App\Package\Segments\Database\BaseQueries\BaseQueryFactory;
use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Comparisons\ModifiedComparison;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\LogicalOperator;
use App\Package\Segments\Values\DateTime\DateTimeFactory;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;
use DoctrineExtensions\Query\Mysql\Field;
use JsonSerializable;

/**
 * Class Segment
 * @package App\Package\Segments
 */
class Segment implements JsonSerializable
{

	/**
	 * @param string $json
	 * @return static
	 * @throws Exceptions\InvalidSegmentInputException
	 * @throws Exceptions\UnknownNodeException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 */
	public static function fromJsonString(string $json): self
	{
		return SegmentFactory::make(
			SegmentInput::fromJsonString($json)
		);
	}

	/**
	 * @param array $data
	 * @return static
	 * @throws Exceptions\InvalidSegmentInputException
	 * @throws Exceptions\UnknownNodeException
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
	 * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
	 * @throws Operators\Logic\Exceptions\InvalidLogicalOperatorException
	 * @throws Values\Arguments\Exceptions\InvalidBooleanException
	 * @throws Values\Arguments\Exceptions\InvalidIntegerException
	 * @throws Values\Arguments\Exceptions\InvalidStringException
	 * @throws Values\DateTime\InvalidDateTimeException
	 * @throws Values\YearDate\Exceptions\InvalidDayException
	 * @throws Values\YearDate\Exceptions\InvalidMonthException
	 * @throws Values\YearDate\Exceptions\InvalidWeekStartDayException
	 * @throws Values\YearDate\Exceptions\InvalidYearDateFormatException
	 */
	public static function fromArray(array $data): self
	{
		return SegmentFactory::make(
			SegmentInput::fromArray($data)
		);
	}

	/**
	 * @var string $weekStart
	 */
	private $weekStart;

	/**
	 * @var string $dateFormat
	 */
	private $dateFormat;

	/**
	 * @var string $baseQueryType
	 */
	private $baseQueryType;

	/**
	 * @var LogicalOperator | Comparison | ModifiedComparison
	 */
	private $root;

	/**
	 * Segment constructor.
	 * @param Comparison|ModifiedComparison|LogicalOperator|null $root
	 * @param string $weekStart
	 * @param string $dateFormat
	 * @param string $baseQueryType
	 */
	public function __construct(
		$root,
		string $weekStart = YearDateRangeFactory::WEEK_START_MONDAY,
		string $dateFormat = DateTimeFactory::INPUT_FORMAT,
		string $baseQueryType = BaseQueryFactory::ORGANIZATION_REGISTRATION
	) {
		$this->root          = $root;
		$this->weekStart     = $weekStart;
		$this->dateFormat    = $dateFormat;
		$this->baseQueryType = $baseQueryType;
	}

	/**
	 * @return string
	 */
	public function getBaseQueryType(): string
	{
		return $this->baseQueryType;
	}

	/**
	 * @return string
	 */
	public function getWeekStart(): string
	{
		return $this->weekStart;
	}

	/**
	 * @return string
	 */
	public function getDateFormat(): string
	{
		return $this->dateFormat;
	}

	/**
	 * @return Comparison|ModifiedComparison|LogicalOperator
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * @return Container
	 */
	public function getRootAsContainer(): Container
	{
		if ($this->root === null) {
			return Container::fromNull();
		}
		if ($this->root instanceof Comparison) {
			return Container::fromComparison($this->root);
		}
		return Container::fromLogicalOperator($this->root);
	}

	/**
	 * @return Field[]
	 */
	public function fields(): array
	{
		$root = $this->root;
		if ($root instanceof Comparison) {
			return [
				$root->getField()
			];
		}
		return $root->fields();
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
		return [
			'weekStart'     => $this->weekStart,
			'dateFormat'    => $this->dateFormat,
			'baseQueryType' => $this->baseQueryType,
			'root'          => $this->root
		];
	}
}
