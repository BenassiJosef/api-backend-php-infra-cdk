<?php

namespace App\Package\Segments\Fields;

/**
 * Interface MultiProperty
 * @package App\Package\Segments\Fields
 */
interface MultiProperty extends Field
{
    /**
     * @return string[]
     */
    public function getProperties(): array;
}