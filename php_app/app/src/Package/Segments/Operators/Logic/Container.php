<?php

namespace App\Package\Segments\Operators\Logic;

use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Operators\Comparisons\ModifiedComparison;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use App\Package\Segments\Fields\Field;
use JsonSerializable;

/**
 * Class Container
 * @package App\Package\Segments\Operators\Logic
 */
class Container implements JsonSerializable
{
	const TYPE_LOGIC = 'logic';

	const TYPE_COMPARISON = 'comparison';

	const TYPE_MODIFIED_COMPARISON = 'modified-comparison';

	const TYPE_NULL = 'null';

	/**
	 * @var bool[]
	 */
	public static $allowedTypes = [
		self::TYPE_LOGIC               => true,
		self::TYPE_COMPARISON          => true,
		self::TYPE_MODIFIED_COMPARISON => true,
	];


	public static function fromNull(): self
	{
		$container = new self();
		$container->isNull = true;
		return $container;
	}

	/**
	 * @param Comparison | ModifiedComparison $comparison
	 * @return static
	 */
	public static function fromComparison(Comparison $comparison): self
	{
		$container             = new self();
		$container->comparison = $comparison;
		return $container;
	}

	/**
	 * @param LogicalOperator $logicalOperator
	 * @return static
	 */
	public static function fromLogicalOperator(LogicalOperator $logicalOperator): self
	{
		$container        = new self();
		$container->logic = $logicalOperator;
		return $container;
	}

	/**
	 * @var Comparison | null $comparison
	 */
	private $comparison;

	/**
	 * @var LogicalOperator | null $logic
	 */
	private $logic;

	/**
	 * @var bool $isNull
	 */
	private $isNull;

	private function __construct()
	{
		$this->isNull = false;
	}

	/**
	 * @return bool
	 */
	public function isNull(): bool
	{
		return $this->isNull;
	}

	/**
	 * @return Comparison
	 * @throws InvalidContainerAccessException
	 */
	public function getAnyComparison(): Comparison
	{
		if ($this->comparison === null) {
			throw new InvalidContainerAccessException(
				$this->getType(),
				self::TYPE_COMPARISON
			);
		}
		return $this->comparison;
	}

	/**
	 * @return Comparison
	 * @throws InvalidContainerAccessException
	 */
	public function getComparison(): Comparison
	{
		if ($this->comparison === null || $this->comparison instanceof ModifiedComparison) {
			throw new InvalidContainerAccessException(
				$this->getType(),
				self::TYPE_COMPARISON
			);
		}
		return $this->comparison;
	}

	/**
	 * @return ModifiedComparison
	 * @throws InvalidContainerAccessException
	 */
	public function getModifiedComparison(): ModifiedComparison
	{
		if (!$this->comparison instanceof ModifiedComparison) {
			throw new InvalidContainerAccessException(
				$this->getType(),
				self::TYPE_MODIFIED_COMPARISON
			);
		}
		return $this->comparison;
	}

	/**
	 * @return LogicalOperator
	 * @throws InvalidContainerAccessException
	 */
	public function getLogic(): LogicalOperator
	{
		if ($this->logic === null) {
			throw new InvalidContainerAccessException(
				$this->getType(),
				self::TYPE_LOGIC
			);
		}
		return $this->logic;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		if ($this->isNull) {
			return self::TYPE_NULL;
		}
		if ($this->logic !== null) {
			return self::TYPE_LOGIC;
		}
		if ($this->comparison instanceof ModifiedComparison) {
			return self::TYPE_MODIFIED_COMPARISON;
		}
		return self::TYPE_COMPARISON;
	}

	/**
	 * @return Field[]
	 */
	public function fields(): array
	{
		$type = $this->getType();
		if ($type === self::TYPE_LOGIC) {
			return $this->logic->fields();
		}
		return [
			$this->comparison->getField()
		];
	}

	/**
	 * @return Comparison|ModifiedComparison|LogicalOperator
	 */
	public function value()
	{
		return $this->comparison ?? $this->logic;
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
		return $this->value();
	}
}
