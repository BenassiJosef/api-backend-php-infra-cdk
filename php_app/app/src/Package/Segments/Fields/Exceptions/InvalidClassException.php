<?php


namespace App\Package\Segments\Fields\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

class InvalidClassException extends BaseException
{
    public function __construct(string $classname)
    {
        parent::__construct(
            "(${classname}) is not a valid class"
        );
    }
}