<?php

namespace App\Package\Segments\Operators\Comparisons\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class InvalidModifierException
 * @package App\Package\Segments\Operators\Comparisons\Exceptions
 */
class InvalidModifierException extends BaseException
{
    public function __construct(string $modifier)
    {
        parent::__construct(
            "(${modifier}) is not a valid modifier",
            400
        );
    }
}