<?php


namespace App\Package\Segments\Operators\Comparisons\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidComparisonModeException
 * @package App\Package\Segments\Operators\Comparisons\Exceptions
 */
class InvalidComparisonModeException extends BaseException
{
    /**
     * InvalidComparisonModeException constructor.
     * @param string $operator
     * @param string[] $allowedModes
     * @param string|null $mode
     */
    public function __construct(
        string $operator,
        array $allowedModes,
        ?string $mode = null
    ) {
        parent::__construct(
            self::message($operator, $allowedModes, $mode),
            StatusCodes::HTTP_BAD_REQUEST
        );
    }

    /**
     * @param string $operator
     * @param string[] $allowedModes
     * @param string|null $mode
     * @return string
     */
    private static function message(
        string $operator,
        array $allowedModes,
        ?string $mode = null
    ): string {
        $allowedModesString = implode(', ', $allowedModes);
        if ($mode === null) {
            return "A mode is required for operator (${operator}) allowed modes are (${allowedModesString})";
        }
        return "(${mode} is not a valid mode for operator (${operator}) allowed modes are (${allowedModesString})";
    }
}