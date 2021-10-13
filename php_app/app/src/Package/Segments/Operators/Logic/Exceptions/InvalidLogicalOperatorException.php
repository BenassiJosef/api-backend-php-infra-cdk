<?php


namespace App\Package\Segments\Operators\Logic\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidLogicalOperatorException
 * @package App\Package\Segments\Operators\Logic\Exceptions
 */
class InvalidLogicalOperatorException extends BaseException
{
    /**
     * InvalidLogicalOperatorException constructor.
     * @param string $operator
     * @param array $allowedOperators
     */
    public function __construct(string $operator, array $allowedOperators)
    {
        $allowedOperatorsString = implode(', ', $allowedOperators);
        parent::__construct(
            "(${operator}) is not a valid logical operator, only (${allowedOperatorsString}) are allowed",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}