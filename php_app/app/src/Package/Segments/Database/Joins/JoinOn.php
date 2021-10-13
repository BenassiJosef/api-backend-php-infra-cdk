<?php

namespace App\Package\Segments\Database\Joins;

use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class JoinOn
 * @package App\Package\Segments\Database\Joins
 */
class JoinOn
{
    const TYPE_TO_MANY = 'toMany';

    const TYPE_TO_ONE = 'toOne';

    /**
     * @param JoinClass $class
     * @param string $propertyName
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public static function validateClassAndProperty(JoinClass $class, string $propertyName): void
    {
        if (!$class->hasProperty($propertyName)) {
            throw new InvalidPropertyException($class->getClassName(), $propertyName);
        }
    }

    /**
     * @var JoinClass $fromClass
     */
    private $fromClass;

    /**
     * @var string $fromProperty
     */
    private $fromProperty;

    /**
     * @var JoinClass $toClass
     */
    private $toClass;

    /**
     * @var string $toProperty
     */
    private $toProperty;

    /**
     * JoinOn constructor.
     * @param JoinClass $fromClass
     * @param string $fromProperty
     * @param JoinClass $toClass
     * @param string $toProperty
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public function __construct(
        JoinClass $fromClass,
        string $fromProperty,
        JoinClass $toClass,
        string $toProperty
    ) {
        self::validateClassAndProperty($fromClass, $fromProperty);
        self::validateClassAndProperty($toClass, $toProperty);
        $this->fromClass    = $fromClass;
        $this->fromProperty = $fromProperty;
        $this->toClass      = $toClass;
        $this->toProperty   = $toProperty;
    }

    /**
     * @return JoinClass
     */
    public function getFromClass(): JoinClass
    {
        return $this->fromClass;
    }

    /**
     * @return string
     */
    public function getFromProperty(): string
    {
        return $this->fromProperty;
    }

    /**
     * @return JoinClass
     */
    public function getToClass(): JoinClass
    {
        return $this->toClass;
    }

    /**
     * @return string
     */
    public function getToProperty(): string
    {
        return $this->toProperty;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->fromProperty === $this->fromClass->getIdPropertyName()) {
            return self::TYPE_TO_MANY;
        }
        return self::TYPE_TO_ONE;
    }

    /**
     * @return $this
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public function inverse(): self
    {
        return new self(
            $this->toClass,
            $this->toProperty,
            $this->fromClass,
            $this->fromProperty
        );
    }
}