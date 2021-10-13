<?php

namespace App\Package\Segments\Values;

/**
 * Interface MultiValue
 * @package App\Package\Segments\Values
 */
interface MultiValue extends Value
{
    /**
     * @return Value[]
     */
    public function values(): array;
}