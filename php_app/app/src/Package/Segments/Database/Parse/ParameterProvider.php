<?php

namespace App\Package\Segments\Database\Parse;

/**
 * Interface ParameterProvider
 * @package App\Package\Segments\Database\Parse
 */
interface ParameterProvider
{
    /**
     * @return array[]
     */
    public function parameters(): array;
}