<?php

namespace App\Package\Segments\Fields;

use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\YearDate;

/**
 * Class MultiField
 * @package App\Package\Segments\Fields
 */
class MultiField implements Field, MultiProperty
{
    /**
     * @param string $key
     * @param string $type
     * @param string $associatedClass
     * @param string ...$propertyNames
     * @return MultiField
     * @throws InvalidClassException
     * @throws InvalidTypeException
     * @throws InvalidPropertiesException
     * @throws SegmentException
     */
    public static function fromValues(
        string $key,
        string $type,
        string $associatedClass,
        string ...$propertyNames
    ): self {
        return new self(
            new BaseField(
                $key,
                $type,
                $associatedClass
            ),
            ...$propertyNames
        );
    }

    /**
     * @param string $className
     * @param string ...$properties
     * @throws InvalidPropertiesException
     * @throws SegmentException
     */
    private static function validatePropertyNames(
        string $className,
        string ...$properties
    ) {
        $missingProperties = from($properties)
            ->where(
                function (string $propertyName) use ($className): bool {
                    return !property_exists($className, $propertyName);
                }
            )
            ->toArray();
        if (count($missingProperties) > 0) {
            throw new InvalidPropertiesException($className, ...$missingProperties);
        }
    }

    /**
     * @var Field $base
     */
    private $base;

    /**
     * @var string[] $associatedProperties
     */
    private $associatedProperties;

    /**
     * MultiField constructor.
     * @param Field $base
     * @param string ...$associatedProperties
     * @throws InvalidPropertiesException
     * @throws SegmentException
     */
    public function __construct(Field $base, string ...$associatedProperties)
    {
        self::validatePropertyNames($base->getAssociatedClass(), ...$associatedProperties);
        $this->base                 = $base;
        $this->associatedProperties = $associatedProperties;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->base->getKey();
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->base->getType();
    }

    /**
     * @inheritDoc
     */
    public function getAssociatedClass(): string
    {
        return $this->base->getAssociatedClass();
    }

    /**
     * @inheritDoc
     */
    public function getProperties(): array
    {
        return $this->associatedProperties;
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
     * @param array $data
     * @param string|null $alias
     * @return string|null
     * @throws InvalidDayException
     * @throws InvalidMonthException
     */
    public function fromArray(array $data, ?string $alias = null): ?string
    {
        $aliasMap = [];
        if ($alias !== null) {
            $aliasMap = [
                'day'   => "${alias}.birthDay",
                'month' => "${alias}.birthMonth",
            ];
        }
        $yearDate = YearDate::fromArray($data, $aliasMap);
        if ($yearDate === null) {
            return null;
        }
        return $yearDate->format();
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
     * @return string
     */
    public function aggregateFunction(): string
    {
        return $this->base->aggregateFunction();
    }
}