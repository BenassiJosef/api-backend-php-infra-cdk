<?php


namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class UnsupportedLogicalOperatorException
 * @package App\Package\Segments\Database\Parse\Exceptions
 */
class UnsupportedLogicalOperatorException extends BaseException
{
    /**
     * UnsupportedLogicalOperatorException constructor.
     * @param string $operator
     * @param string[] $supportedOperators
     */
    public function __construct(string $operator, array $supportedOperators)
    {
        $supportedOperatorsString = implode(', ', $supportedOperators);
        parent::__construct(
            "(${operator}) is not a supported logical operator for"
            . " database parsing, only (${supportedOperatorsString}) are supported"
        );
    }
}