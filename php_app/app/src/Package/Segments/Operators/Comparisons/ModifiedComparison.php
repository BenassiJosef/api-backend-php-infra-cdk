<?php

namespace App\Package\Segments\Operators\Comparisons;

/**
 * Interface ModifiedComparison
 * @package App\Package\Segments\Operators\Comparisons
 */
interface ModifiedComparison extends Comparison
{
    /**
     * @return string
     */
    public function getModifier(): string;
}