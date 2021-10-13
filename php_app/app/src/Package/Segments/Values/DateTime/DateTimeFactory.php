<?php

namespace App\Package\Segments\Values\DateTime;

use App\Package\Segments\Values\Arguments\ArgumentValue;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\Value;
use DateTimeImmutable;
use Throwable;

/**
 * Class DateTimeFactory
 * @package App\Package\Segments\Values\DateTime
 */
class DateTimeFactory
{
	const INPUT_FORMAT = 'Y-m-d H:i:s';

	const HOUR       = 'hour';
	const DAY        = 'day';
	const WEEK       = 'week';
	const MONTH      = 'month';
	const QUARTER    = 'quarter';
	const SIX_MONTHS = 'six-months';
	const YEAR       = 'year';
	const TWO_YEARS  = 'two-years';

	/**
	 * @var string[]
	 */
	public static $specialFormats = [
		self::HOUR       => '-1 hour',
		self::DAY        => '-1 day',
		self::WEEK       => '-1 week',
		self::MONTH      => '-1 month',
		self::QUARTER    => '-3 months',
		self::SIX_MONTHS => '-6 months',
		self::YEAR       => '-1 year',
		self::TWO_YEARS  => '-2 years',
	];

	/**
	 * @var string $inputFormat
	 */
	private $inputFormat;

	/**
	 * @var DateTimeImmutable $today
	 */
	private $today;

	/**
	 * DateTimeFactory constructor.
	 * @param string $inputFormat
	 * @param DateTimeImmutable | null $today
	 */
	public function __construct(
		string $inputFormat = self::INPUT_FORMAT,
		?DateTimeImmutable $today = null
	) {
		if ($today === null) {
			$today = new DateTimeImmutable();
		}
		$this->inputFormat = $inputFormat;
		$this->today       = $today;
	}

	/**
	 * @param string $name 
	 * @param $rawValue
	 * @return Value
	 * @throws InvalidDateTimeException
	 * @throws InvalidStringException
	 */
	public function fromString(string $name, $rawValue): Value
	{
		if (!is_string($rawValue)) {
			throw new InvalidStringException($name, $rawValue);
		}
		if (array_key_exists($rawValue, self::$specialFormats)) {
			$special = self::$specialFormats[$rawValue];
			return ArgumentValue::dateTimeValue(
				$rawValue,
				$name,
				$this->today->modify($special)
			);
		}

		$dateTime = DateTimeImmutable::createFromFormat($this->inputFormat, $rawValue);
		if ($dateTime === false) {
			throw new InvalidDateTimeException(
				$rawValue,
				$this->inputFormat,
				self::$specialFormats
			);
		}
		return ArgumentValue::dateTimeValue(
			$rawValue,
			$name,
			$dateTime
		);
	}

	/**
	 * @return string[]
	 */
	public function specialFormats(): array
	{
		return self::$specialFormats;
	}
}
