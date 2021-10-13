<?php


namespace App\Package\Segments\Exceptions;

use App\Package\Exceptions\BaseException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidSegmentInputException
 * @package App\Package\Segments\Exceptions
 */
class InvalidSegmentInputException extends BaseException
{
    public function __construct(array $keys, array $requiredKeys)
    {
        $keysString         = implode(', ', $keys);
        $requiredKeysString = implode(', ', $requiredKeys);
        parent::__construct(
            "(${keysString}) does not match the require keys for a segment (${requiredKeysString})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}