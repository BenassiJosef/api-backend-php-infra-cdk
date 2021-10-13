<?php

namespace App\Package\Segments\Fields;

/**
 * Interface DeAliaser
 * @package App\Package\Segments\Fields
 */
interface DeAliaser extends MultiProperty
{
    /**
     * @param string $attributeName
     * @return string
     */
    public function deAlias(string $attributeName): string;
}