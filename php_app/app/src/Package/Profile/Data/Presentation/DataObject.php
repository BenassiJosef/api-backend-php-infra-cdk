<?php

namespace App\Package\Profile\Data\Presentation;

use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\ObjectDefinition;
use JsonSerializable;

/**
 * Class DataObject
 * @package App\Package\Profile\Data\Presentation
 */
class DataObject implements JsonSerializable
{
    /**
     * @var ObjectDefinition $objectDefinition
     */
    private $objectDefinition;

    /**
     * @var array $rows
     */
    private $rows = [];

    /**
     * DataObject constructor.
     * @param ObjectDefinition $objectDefinition
     * @param array $rows
     */
    public function __construct(
        ObjectDefinition $objectDefinition,
        array $rows = []
    ) {
        $this->objectDefinition = $objectDefinition;
        $this->addRows($rows);
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->objectDefinition->name();
    }

    /**
     * @param array $rows
     * @return DataObject
     */
    public function addRows(array $rows): DataObject
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addRow(array $data): DataObject
    {
        $definition = $this->objectDefinition;
        if (!$definition instanceof Filterable) {
            $this->rows[] = $data;
            return $this;
        }
        foreach ($definition->filters() as $filter) {
            $data = $filter->filter($data);
        }
        $this->rows[] = $data;
        return $this;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'rows' => $this->rows,
        ];
    }
}