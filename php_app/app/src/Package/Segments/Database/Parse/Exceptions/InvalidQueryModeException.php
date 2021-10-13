<?php


namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class InvalidQueryModeException
 * @package App\Package\Segments\Database\Parse\Exceptions
 */
class InvalidQueryModeException extends BaseException
{
    /**
     * InvalidQueryModeException constructor.
     * @param string $mode
     * @param string[] $validModes
     */
    public function __construct(
        string $mode,
        array $validModes
    ) {
        $validModesString = implode(', ', $validModes);
        parent::__construct(
            "(${mode}) is not a valid mode, only (${validModesString}) are valid modes"
        );
    }
}