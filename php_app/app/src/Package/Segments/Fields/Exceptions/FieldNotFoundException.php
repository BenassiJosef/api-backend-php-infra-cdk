<?php

namespace App\Package\Segments\Fields\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class FieldNotFoundException
 * @package App\Package\Segments\Fields\Exceptions
 */
class FieldNotFoundException extends BaseException
{
    public function __construct(string $key)
    {
        parent::__construct(
            "Field with key ${key} cannot be found",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}