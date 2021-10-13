<?php

namespace App\Package\Segments\Fields;

/**
 * Interface SingleProperty
 * @package App\Package\Segments\Fields
 */
interface SingleProperty extends Field
{
    /**
     * @return string
     */
    public function getProperty(): string;
}
