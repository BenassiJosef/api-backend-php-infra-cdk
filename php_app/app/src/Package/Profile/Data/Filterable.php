<?php

namespace App\Package\Profile\Data;

/**
 * Interface Filterable
 * @package App\Package\Profile\Data
 */
interface Filterable
{
    /**
     * @return Filter[]
     */
    public function filters(): array;
}