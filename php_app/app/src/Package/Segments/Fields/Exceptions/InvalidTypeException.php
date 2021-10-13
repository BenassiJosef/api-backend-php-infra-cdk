<?php


namespace App\Package\Segments\Fields\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

class InvalidTypeException extends BaseException
{
    public function __construct(string $type, array $allowedTypes)
    {
        $typeListString = implode(', ', $allowedTypes);
        parent::__construct(
            "(${type}) is not a valid type, only (${typeListString}) are valid types",
            400
        );
    }
}