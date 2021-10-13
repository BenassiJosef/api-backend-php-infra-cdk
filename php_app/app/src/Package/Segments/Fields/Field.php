<?php

namespace App\Package\Segments\Fields;

use JsonSerializable;

/**
 * Interface Field
 * @package App\Package\Segments\Fields
 */
interface Field extends JsonSerializable
{
    const TYPE_STRING = 'string';

    const TYPE_INTEGER = 'integer';

    const TYPE_BOOLEAN = 'boolean';

    const TYPE_DATETIME = 'datetime';

    const TYPE_YEARDATE = 'yeardate';

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getAssociatedClass(): string;

    /**
     * @param array $data
     * @param string|null $alias
     * @return string|int|bool|null
     */
    public function fromArray(array $data, ?string $alias = null);

    /**
     * @param string $property
     * @return string
     */
    public function formatAsAggregate(string $property): string;

    /**
     * @return string
     */
    public function aggregateFunction(): string;
}