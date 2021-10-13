<?php


namespace App\Package\Segments\Database\Joins\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class InvalidClassException
 * @package App\Package\Segments\Database\Joins\Exceptions
 */
class InvalidClassException extends BaseException
{
    public function __construct(string $className)
    {
        parent::__construct(
            "(${className}) is not a class that exists"
        );
    }
}