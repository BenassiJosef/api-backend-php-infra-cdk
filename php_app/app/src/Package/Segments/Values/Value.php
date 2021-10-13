<?php


namespace App\Package\Segments\Values;

use App\Package\Segments\Values\Arguments\Argument;
use JsonSerializable;

/**
 * Interface Value
 * @package App\Package\Segments\Values
 */
interface Value extends JsonSerializable
{
    /**
     * @return string | int | bool
     */
    public function rawValue();

    /**
     * @return Argument[]
     */
    public function arguments(): array;
}