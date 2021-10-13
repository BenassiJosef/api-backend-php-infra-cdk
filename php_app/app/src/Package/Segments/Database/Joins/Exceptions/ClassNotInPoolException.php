<?php

namespace App\Package\Segments\Database\Joins\Exceptions;

use App\Package\Exceptions\BaseException;

/**
 * Class ClassNotInPoolException
 * @package App\Package\Segments\Database\Joins\Exceptions
 */
class ClassNotInPoolException extends BaseException
{
    public function __construct(string $className)
    {
        parent::__construct(
            "(${className}) cannot be found in this ClassPool or it does not exist"
        );
    }
}