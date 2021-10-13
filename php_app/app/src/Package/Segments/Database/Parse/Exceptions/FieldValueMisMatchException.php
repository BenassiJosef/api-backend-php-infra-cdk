<?php


namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\MultiProperty;
use App\Package\Segments\Values\Arguments\Argument;

/**
 * Class FieldValueMisMatchException
 * @package App\Package\Segments\Database\Parse\Exceptions
 */
class FieldValueMisMatchException extends BaseException
{
    public function __construct(
        Argument $argument,
        MultiProperty $field
    ) {
        $argumentName        = $argument->getName();
        $fieldKey            = $field->getKey();
        $availableProperties = implode(', ', $field->getProperties());
        parent::__construct(
            "(${argumentName}) is not an available property on"
            . " field (${fieldKey}) only (${availableProperties}) are allowed"
        );
    }
}