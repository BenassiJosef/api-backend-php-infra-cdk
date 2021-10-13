<?php

namespace App\Package\Segments\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Reach;

/**
 * Class InvalidReachInput
 * @package App\Package\Segments\Exceptions
 */
class InvalidReachInputException extends BaseException
{
    /**
     * InvalidReachInput constructor.
     * @param array $reachInput
     */
    public function __construct(array $reachInput)
    {
        $jsonReachInput = json_encode($reachInput);
        $expectedJson   = json_encode(new Reach());
        parent::__construct(
            "Got a Reach that looks like (${jsonReachInput}), expected it to look like (${expectedJson})"
        );
    }
}