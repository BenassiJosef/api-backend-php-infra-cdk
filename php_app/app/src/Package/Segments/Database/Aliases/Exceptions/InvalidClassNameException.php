<?php

namespace App\Package\Segments\Database\Aliases\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

class InvalidClassNameException extends BaseException
{
    public function __construct(string $className)
    {
        parent::__construct(
            "(${className}) is not a class that an alias can be generated for"
        );
    }
}