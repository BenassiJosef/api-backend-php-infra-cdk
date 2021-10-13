<?php

namespace App\Package\Segments\Database\Aliases;

/**
 * Interface TableAliaser
 * @package App\Package\Segments\Database
 */
interface TableAliaser
{
    /**
     * @param string $className
     * @return string
     */
    public function alias(string $className): string;

    /**
     * @param string $className
     * @param string $propertyName
     * @return string
     */
    public function aliasPropertyName(string $className, string $propertyName): string;

    /**
     * @param string $className
     * @return bool
     */
    public function hasClassName(string $className): bool;
}