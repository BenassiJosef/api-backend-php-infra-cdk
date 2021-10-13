<?php

namespace App\Package\Segments\Exceptions;

use App\Package\Exceptions\BaseException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class UnknownNodeException
 * @package App\Package\Segments\Exceptions
 */
class UnknownNodeException extends BaseException
{
    public function __construct(array $node)
    {
        $nodeJson = json_encode($node);
        parent::__construct(
            "(${nodeJson}) is not recognised as a part of a segment",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}