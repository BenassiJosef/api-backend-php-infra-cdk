<?php

namespace App\Package\Segments\Fields;

use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;

class BaseField implements Field
{
    public static $types = [
        Field::TYPE_STRING   => true,
        Field::TYPE_INTEGER  => true,
        Field::TYPE_BOOLEAN  => true,
        Field::TYPE_DATETIME => true,
        Field::TYPE_YEARDATE => true,
    ];

    /**
     * @param string $type
     * @throws InvalidTypeException
     */
    private static function validateType(string $type): void
    {
        if (!array_key_exists($type, self::$types)) {
            throw new InvalidTypeException($type, array_keys(self::$types));
        }
    }

    /**
     * @param string $classname
     * @throws InvalidClassException
     */
    private static function validateClassname(string $classname): void
    {
        if (!class_exists($classname)) {
            throw new InvalidClassException($classname);
        }
    }

    /**
     * @var string $key
     */
    private $key;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $associatedClass
     */
    private $associatedClass;

    /**
     * @var string | null $aggregateFunction
     */
    private $aggregateFunction;

    /**
     * @var bool | null $aggregateDistinct
     */
    private $aggregateDistinct;

    /**
     * BaseField constructor.
     * @param string $key
     * @param string $type
     * @param string $associatedClass
     * @param string|null $aggregateFunction
     * @param string|null $aggregateDistinct
     * @throws InvalidClassException
     * @throws InvalidTypeException
     */
    public function __construct(
        string $key,
        string $type,
        string $associatedClass,
        ?string $aggregateFunction = null,
        ?string $aggregateDistinct = null
    ) {
        self::validateType($type);
        self::validateClassname($associatedClass);
        $this->key               = $key;
        $this->type              = $type;
        $this->associatedClass   = $associatedClass;
        $this->aggregateFunction = $aggregateFunction;
        $this->aggregateDistinct = $aggregateDistinct;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getAssociatedClass(): string
    {
        return $this->associatedClass;
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
        return $this->key;
    }

    /**
     * @param array $data
     * @param string|null $alias
     * @return string|null
     */
    public function fromArray(array $data, ?string $alias = null): ?string
    {
        return json_encode($data);
    }

    /**
     * @param string $property
     * @return string
     */
    public function formatAsAggregate(string $property): string
    {
        $aggregateFunction = $this->aggregateFunction();
        if ($this->aggregateDistinct()) {
            return "${aggregateFunction}(DISTINCT ${property})";
        }
        return "${aggregateFunction}(${property})";
    }

    private function aggregateDistinct(): bool
    {
        if ($this->aggregateDistinct !== null) {
            return $this->aggregateDistinct;
        }
        switch ($this->type) {
            case self::TYPE_STRING:
                return true;
            case self::TYPE_INTEGER:
            case self::TYPE_BOOLEAN:
            case self::TYPE_DATETIME:
            default:
                return false;
        }
    }

    public function aggregateFunction(): string
    {
        if ($this->aggregateFunction !== null) {
            return $this->aggregateFunction;
        }
        switch ($this->type) {
            case self::TYPE_BOOLEAN:
            case self::TYPE_STRING:
                return 'GROUP_CONCAT';
            case self::TYPE_INTEGER:
                return 'SUM';
            case self::TYPE_DATETIME:
                return 'MIN';
            default:
                return 'MAX';
        }
    }
}