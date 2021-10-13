<?php

namespace App\Package\Segments\Metadata;

use JsonSerializable;

/**
 * Class MetadataType
 * @package App\Package\Segments\Metadata
 */
class MetadataType implements JsonSerializable
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var MetadataOperator[] $operators
     */
    private $operators;

    /**
     * @var string[] $specialValues
     */
    private $specialValues;

    /**
     * MetadataType constructor.
     * @param string $name
     * @param MetadataOperator[] $operators
     * @param string[] $specialValues
     */
    public function __construct(
        string $name,
        array $operators,
        array $specialValues
    ) {
        $this->name          = $name;
        $this->operators     = $operators;
        $this->specialValues = $specialValues;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return MetadataOperator[]
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * @return string[]
     */
    public function getSpecialValues(): array
    {
        return $this->specialValues;
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
        $output = [
            'name'      => $this->name,
            'operators' => $this->operators
        ];
        if (count($this->specialValues) > 0) {
            $output['specialValues'] = $this->specialValues;
        }
        return $output;
    }
}