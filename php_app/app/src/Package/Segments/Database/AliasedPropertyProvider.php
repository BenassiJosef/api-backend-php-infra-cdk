<?php


namespace App\Package\Segments\Database;

use App\Package\Segments\Fields\Field;
use App\Package\Segments\Values\Arguments\Argument;

/**
 * Interface AliasedPropertyProvider
 * @package App\Package\Segments\Database
 */
interface AliasedPropertyProvider
{
    /**
     * @param Field $field
     * @param Argument $argument
     * @return string
     */
    public function propertyName(Field $field, Argument $argument): string;
}