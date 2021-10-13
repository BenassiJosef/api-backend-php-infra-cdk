<?php


namespace App\Package\Segments\Operators\Logic\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidContainerAccessException
 * @package App\Package\Segments\Operators\Logic\Exceptions
 */
class InvalidContainerAccessException extends BaseException
{
    /**
     * InvalidContainerAccessException constructor.
     * @param string $type
     * @param string $accessedAs
     */
    public function __construct(string $type, string $accessedAs)
    {
        parent::__construct(
            "Cannot use container of type (${type}) as (${accessedAs})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}