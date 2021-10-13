<?php

namespace App\Package\Segments\Fields;

use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Values\Arguments\ArgumentValue;

class StandardField implements Field, SingleProperty
{
	/**
	 * @param string $key
	 * @param string $associatedClass
	 * @param string|null $propertyName
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 */
	public static function datetime(
		string $key,
		string $associatedClass,
		?string $propertyName = null
	): self {
		return self::fromValues(
			$key,
			BaseField::TYPE_DATETIME,
			$associatedClass,
			$propertyName
		);
	}

	/**
	 * @param string $key
	 * @param string $associatedClass
	 * @param string|null $propertyName
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 */
	public static function boolean(
		string $key,
		string $associatedClass,
		?string $propertyName = null
	): self {
		return self::fromValues(
			$key,
			BaseField::TYPE_BOOLEAN,
			$associatedClass,
			$propertyName
		);
	}

	/**
	 * @param string $key
	 * @param string $associatedClass
	 * @param string|null $propertyName
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 */
	public static function integer(
		string $key,
		string $associatedClass,
		?string $propertyName = null,
		?string $aggregateFunction = null
	): self {
		return self::fromValues(
			$key,
			BaseField::TYPE_INTEGER,
			$associatedClass,
			$propertyName,
			$aggregateFunction
		);
	}

	/**
	 * @param string $key
	 * @param string $associatedClass
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 * @throws SegmentException
	 */
	public static function integerId(
		string $associatedClass,
		string $key = 'id'
	): self {
		return self::fromValues(
			$key,
			BaseField::TYPE_INTEGER,
			$associatedClass,
			$key,
			'MAX',
			true
		);
	}

	/**
	 * @param string $key
	 * @param string $associatedClass
	 * @param string|null $propertyName
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 */
	public static function string(
		string $key,
		string $associatedClass,
		?string $propertyName = null
	): self {
		return self::fromValues(
			$key,
			BaseField::TYPE_STRING,
			$associatedClass,
			$propertyName
		);
	}

	/**
	 * @param string $key
	 * @param string $type
	 * @param string $associatedClass
	 * @param string|null $propertyName
	 * @param string|null $aggregateFunction
	 * @param string|null $aggregateDistinct
	 * @return StandardField
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 * @throws InvalidTypeException
	 * @throws SegmentException
	 */
	private static function fromValues(
		string $key,
		string $type,
		string $associatedClass,
		?string $propertyName = null,
		?string $aggregateFunction = null,
		?string $aggregateDistinct = null
	) {
		return new self(
			new BaseField(
				$key,
				$type,
				$associatedClass,
				$aggregateFunction,
				$aggregateDistinct
			),
			$propertyName
		);
	}

	/**
	 * @var Field $base
	 */
	private $base;

	/**
	 * @var string $associatedProperty
	 */
	private $associatedProperty;

	/**
	 * @param string $classname
	 * @param string $propertyName
	 * @throws InvalidPropertiesException
	 * @throws SegmentException
	 */
	private static function validatePropertyName(string $classname, string $propertyName)
	{
		if (!property_exists($classname, $propertyName)) {
			throw new InvalidPropertiesException($classname, $propertyName);
		}
	}

	/**
	 * StandardField constructor.
	 * @param Field $base
	 * @param string|null $associatedProperty
	 * @throws InvalidPropertiesException
	 * @throws SegmentException
	 */
	public function __construct(
		Field $base,
		?string $associatedProperty = null
	) {
		if ($associatedProperty === null) {
			$associatedProperty = $base->getKey();
		}
		self::validatePropertyName($base->getAssociatedClass(), $associatedProperty);
		$this->base               = $base;
		$this->associatedProperty = $associatedProperty;
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->base->getKey();
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->base->getType();
	}

	/**
	 * @return string
	 */
	public function getAssociatedClass(): string
	{
		return $this->base->getAssociatedClass();
	}

	/**
	 * @return string
	 */
	public function getProperty(): string
	{
		return $this->associatedProperty;
	}

	/**
	 * @param array $data
	 * @param string|null $alias
	 * @return string|null
	 */
	public function fromArray(array $data, ?string $alias = null)
	{
		$propertyName = $this->associatedProperty;
		if ($alias !== null) {
			$propertyName = "${alias}.${propertyName}";
		}
		return $this->format($data[$propertyName]);
	}

	private function format($value)
	{
		switch ($this->getType()) {
			case Field::TYPE_INTEGER:
				return intval($value);
			case Field::TYPE_BOOLEAN:
				return boolval($value);
			default:
				return $value;
		}
	}

	/**
	 * @param string $property
	 * @return string
	 */
	public function formatAsAggregate(string $property): string
	{
		return $this->base->formatAsAggregate($property);
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
		return $this->base->jsonSerialize();
	}

	/**
	 * @return string
	 */
	public function aggregateFunction(): string
	{
		return $this->base->aggregateFunction();
	}
}
