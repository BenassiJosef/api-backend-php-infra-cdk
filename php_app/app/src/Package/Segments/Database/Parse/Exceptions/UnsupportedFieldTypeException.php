<?php


namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\Field;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Throwable;

class UnsupportedFieldTypeException extends BaseException
{
    public function __construct(Field $field)
    {
        $type = get_class($field);
        parent::__construct(
            "Field of type (${type}) is not supported for parsing into a Doctrine query"
        );
    }
}