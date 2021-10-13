<?php


namespace App\Package\Segments;

use App\Package\Segments\Operators\Comparisons\ComparisonFactory;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Logic\LogicalOperatorFactory;

/**
 * Class SegmentFactory
 * @package App\Package\Segments
 */
class SegmentFactory
{
	/**
	 * @param SegmentInput $input
	 * @return Segment
	 * @throws Fields\Exceptions\FieldNotFoundException
	 * @throws Fields\Exceptions\InvalidClassException
	 * @throws Fields\Exceptions\InvalidPropertiesException
	 * @throws Fields\Exceptions\InvalidPropertyAliasException
	 * @throws Fields\Exceptions\InvalidTypeException
	 * @throws Operators\Comparisons\Exceptions\InvalidComparisonModeException
	 * @throws Operators\Comparisons\Exceptions\InvalidModifierException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorException
	 * @throws Operators\Comparisons\Exceptions\InvalidOperatorForTypeException
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
	public static function make(SegmentInput $input): Segment
	{
		$comparisonFactory      = ComparisonFactory::configure(
			$input->getWeekStart(),
			$input->getDateFormat()
		);
		$logicalOperatorFactory = new LogicalOperatorFactory(
			$comparisonFactory
		);
		$rootInput              = $input->getRoot();
		if ($rootInput instanceof ComparisonInput) {
			return new Segment(
				$comparisonFactory->make($rootInput),
				$input->getWeekStart(),
				$input->getDateFormat(),
				$input->getBaseQueryType()
			);
		}
		$operator = null;

		if ($rootInput !== null) {
			$operator = $logicalOperatorFactory->make($rootInput);
		}
		return new Segment(
			$operator,
			$input->getWeekStart(),
			$input->getDateFormat(),
			$input->getBaseQueryType()
		);
	}
}
