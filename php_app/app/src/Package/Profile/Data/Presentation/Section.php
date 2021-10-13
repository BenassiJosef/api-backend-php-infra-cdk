<?php

namespace App\Package\Profile\Data\Presentation;

use JsonSerializable;

/**
 * Class Section
 * @package App\Package\Profile\Data\Presentation
 */
class Section implements JsonSerializable
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var DataObject[] $objects
     */
    private $objects;

    /**
     * Section constructor.
     * @param string $name
     * @param DataObject[] $objects
     */
    public function __construct(
        string $name,
        array $objects = []
    ) {
        $this->name    = $name;
        $this->objects = $objects;
    }

    /**
     * @param DataObject $object
     * @return Section
     */
    public function add(DataObject $object) : Section
    {
        $this->objects[$object->getName()] = $object;
        return $this;
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        return from($this->objects)
            ->select(function (DataObject $object): int {
                return $object->count();
            })
            ->sum();
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
            'name'    => $this->name,
            'objects' => from($this->objects)
                ->where(function (DataObject $object): bool {
                    return $object->count() > 0;
                })
                ->toValues()
                ->toArray(),
        ];
    }
}