<?php

namespace App\Package\Segments\Fields\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class InvalidPropertyAliasException
 * @package App\Package\Segments\Fields\Exceptions
 */
class InvalidPropertyAliasException extends BaseException
{
    /**
     * InvalidPropertyAliasException constructor.
     * @param array $availableProperties
     * @param string $alias
     * @param string $invalidProperty
     */
    public function __construct(array $availableProperties, string $alias, string $invalidProperty)
    {
        $availablePropertyString = implode(', ', $availableProperties);
        parent::__construct(
            "(${invalidProperty}) is not a valid property".
            " for alias (${alias}) only (${availablePropertyString}) are valid."
        );
    }
}