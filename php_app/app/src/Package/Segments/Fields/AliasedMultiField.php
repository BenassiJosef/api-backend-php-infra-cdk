<?php

namespace App\Package\Segments\Fields;

use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Values\ValueFormatter;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\YearDate;

/**
 * Class AliasedMultiField
 * @package App\Package\Segments\Fields
 */
class AliasedMultiField implements MultiProperty, DeAliaser
{
    /**
     * @param string $key
     * @param string $associatedClass
     * @param array $aliases
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     */
    public static function yeardate(
        string $key,
        string $associatedClass,
        array $aliases
    ): self {
        return self::fromValues(
            $key,
            Field::TYPE_YEARDATE,
            $associatedClass,
            $aliases
        );
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $associatedClass
     * @param array $aliasedProperties
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidTypeException
     * @throws InvalidPropertyAliasException
     */
    private static function fromValues(
        string $key,
        string $type,
        string $associatedClass,
        array $aliasedProperties
    ): self {
        return new self(
            MultiField::fromValues(
                $key,
                $type,
                $associatedClass,
                ...array_unique(array_values($aliasedProperties))
            ),
            $aliasedProperties
        );
    }

    /**
     * @param MultiProperty $field
     * @param array $aliases
     * @throws InvalidPropertyAliasException
     */
    public static function validateAliases(MultiProperty $field, array $aliases)
    {
        $properties = $field->getProperties();
        foreach ($aliases as $alias => $property) {
            if (!in_array($property, $properties)) {
                throw new InvalidPropertyAliasException($properties, $alias, $property);
            }
        }
    }

    /**
     * @var MultiProperty $base
     */
    private $base;

    /**
     * @var string[] $aliases
     */
    private $aliases;

    /**
     * AliasedMultiField constructor.
     * @param MultiProperty $base
     * @param string[] $aliases
     * @throws InvalidPropertyAliasException
     */
    public function __construct(MultiProperty $base, array $aliases)
    {
        self::validateAliases($base, $aliases);
        $this->base    = $base;
        $this->aliases = $aliases;
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
        return $this->base->getProperties();
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function deAlias(string $attributeName): string
    {
        if (!array_key_exists($attributeName, $this->aliases)) {
            return $attributeName;
        }
        return $this->aliases[$attributeName];
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
        $aliasMap = $this->aliases;
        if ($alias !== null) {
            $aliasMap = from($aliasMap)
                ->select(
                    function (string $value) use ($alias): string {
                        return "${alias}.${value}";
                    },
                    function (string $value, string $key): string {
                        return $key;
                    }
                )
                ->toArray();
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
