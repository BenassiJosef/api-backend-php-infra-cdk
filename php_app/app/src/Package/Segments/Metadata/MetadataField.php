<?php

namespace App\Package\Segments\Metadata;

use App\Package\Segments\Fields\Field;
use JsonSerializable;

/**
 * Class MetadataField
 * @package App\Package\Segments\Metadata
 */
class MetadataField implements JsonSerializable
{
    /**
     * @var Field $field
     */
    private $field;

    /**
     * MetaDataField constructor.
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->field->getKey();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->field->getType();
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
        return [
            'key'  => $this->getKey(),
            'type' => $this->getType(),
        ];
    }
}