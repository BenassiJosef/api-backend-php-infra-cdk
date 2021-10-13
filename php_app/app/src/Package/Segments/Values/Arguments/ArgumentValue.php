<?php

namespace App\Package\Segments\Values\Arguments;

use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\ValueFormatter;
use DateTime;
use App\Package\Segments\Values\Value;
use DateTimeImmutable;

/**
 * Class ArgumentValue
 * @package App\Package\Segments\Values\Arguments
 */
class ArgumentValue implements Value, Argument, ValueFormatter
{
    /**
     * @param string $name
     * @param $rawValue
     * @return static
     * @throws InvalidStringException
     */
    public static function stringValue(string $name, $rawValue): self
    {
        if (!is_string($rawValue)) {
            throw new InvalidStringException($name, $rawValue);
        }
        return new self(
            $name,
            $rawValue
        );
    }

    /**
     * @param string $name
     * @param $rawValue
     * @return $this
     * @throws InvalidBooleanException
     */
    public static function booleanValue(string $name, $rawValue): self
    {
        if (!is_bool($rawValue)) {
            throw new InvalidBooleanException($name, $rawValue);
        }
        return new self(
            $name,
            $rawValue
        );
    }

    /**
     * @param string $name
     * @param $rawValue
     * @return $this
     * @throws InvalidIntegerException
     */
    public static function integerValue(string $name, $rawValue): self
    {
        if (!is_integer($rawValue)) {
            throw new InvalidIntegerException($name, $rawValue);
        }
        return new self(
            $name,
            $rawValue
        );
    }

    /**
     * @param string $rawValue
     * @param string $name
     * @param DateTimeImmutable $dateTime
     * @return static
     */
    public static function dateTimeValue(string $rawValue, string $name, DateTimeImmutable $dateTime): self
    {
        return new self(
            $name,
            $dateTime,
            $rawValue
        );
    }

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string | int | DateTime $value
     */
    private $value;

    /**
     * @var DateTime|int|string|null $rawValue
     */
    private $rawValue;

    /**
     * Argument constructor.
     * @param string $name
     * @param DateTime|int|string $value
     * @param DateTime|int|string|null $rawValue
     */
    public function __construct(string $name, $value, $rawValue = null)
    {
        if ($rawValue === null) {
            $rawValue = $value;
        }
        $this->name     = $name;
        $this->value    = $value;
        $this->rawValue = $rawValue;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DateTime|int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string | int | bool
     */
    public function rawValue()
    {
        return $this->rawValue;
    }

    /**
     * @return ArgumentValue[]
     */
    public function arguments(): array
    {
        return [
            $this
        ];
    }

    /**
     * @return string | int | boolean | null
     */
    public function format()
    {
        return $this->value;
    }

    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return $this->rawValue;
    }
}