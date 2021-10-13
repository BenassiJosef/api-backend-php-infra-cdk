<?php


namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Field;
use App\Package\Segments\Values\MultiValue;
use App\Package\Segments\Values\Value;
use JsonSerializable;

/**
 * Interface Comparison
 * @package App\Package\Segments\Operators\Comparisons
 */
interface Comparison extends JsonSerializable
{
    /**
     * @return Field
     */
    public function getField(): Field;

    /**
     * @return string
     */
    public function getOperator(): string;

    /**
     * @return Value | MultiValue
     */
    public function getValue(): Value;
}