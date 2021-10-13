<?php

namespace App\Package\Segments\Values\Arguments;

use DateTime;
use App\Package\Segments\Values\Value;

/**
 * Interface Argument
 * @package App\Package\Segments\Values\Arguments
 */
interface Argument extends Value
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return DateTime|int|string
     */
    public function getValue();
}