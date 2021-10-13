<?php

namespace App\Package\Segments\Metadata;

use App\Package\Segments\Fields\Field;
use App\Package\Segments\Fields\FieldList;
use App\Package\Segments\Operators\Comparisons\ComparisonFactory;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Values\ValueFactory;
use JsonSerializable;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;

/**
 * Class Metadata
 * @package App\Package\Segments\Metadata
 */
class Metadata implements JsonSerializable
{
    /**
     * @var ValueFactory $valueFactory
     */
    private $valueFactory;

    /**
     * @var ComparisonFactory $comparisonFactory
     */
    private $comparisonFactory;

    /**
     * @var FieldList $fieldList
     */
    private $fieldList;

    /**
     * Metadata constructor.
     * @param ValueFactory $valueFactory
     * @param ComparisonFactory $comparisonFactory
     * @param FieldList $fieldList
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidTypeException
     */
    public function __construct(
        ValueFactory $valueFactory,
        ComparisonFactory $comparisonFactory,
        ?FieldList $fieldList = null
    ) {
        if ($fieldList === null) {
            $fieldList = FieldList::default();
        }
        $this->valueFactory      = $valueFactory;
        $this->comparisonFactory = $comparisonFactory;
        $this->fieldList         = $fieldList;
    }


    /**
     * @return MetadataField[]
     */
    public function fields(): array
    {
        return from($this->fieldList->fields())
            ->select(
                function (Field $field): MetadataField {
                    return new MetadataField($field);
                }
            )
            ->toArray();
    }

    /**
     * @return MetadataType[]
     * @throws InvalidTypeException
     * @throws InvalidOperatorException
     */
    public function types(): array
    {
        $metadataTypes = [];
        $types         = $this->valueFactory->types();
        foreach ($types as $type) {
            $metadataOperators = [];
            $operators         = $this->comparisonFactory->allowedOperatorsForType($type);
            foreach ($operators as $operator) {
                $metadataOperators[] = new MetadataOperator(
                    $operator,
                    $this->comparisonFactory->modifiersForTypeAndOperator($type, $operator)
                );
            }
            $specialValues   = $this->valueFactory->specialValuesForType($type);
            $metadataTypes[] = new MetadataType($type, $metadataOperators, $specialValues);
        }
        return $metadataTypes;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     * @throws InvalidTypeException
     */
    public function jsonSerialize()
    {
        return [
            'fields' => $this->fields(),
            'types'  => $this->types(),
        ];
    }
}