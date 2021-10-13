<?php


namespace App\Package\Segments\Values\DateTime;

use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidDateTimeException
 * @package App\Package\Segments\Values\DateTime
 */
class InvalidDateTimeException extends SegmentException
{
    /**
     * InvalidDateTimeException constructor.
     * @param string $format
     * @param string $expectedFormat
     * @param string[] $specialFormats
     */
    public function __construct(
        string $format,
        string $expectedFormat,
        array $specialFormats
    ) {
        $specialFormats = from($specialFormats)
            ->toKeys()
            ->aggregate(
                function (string $aggregate, string $key): string {
                    return "${aggregate}, ${key}";
                }
            );
        parent::__construct(
            "(${format}) is not a valid date. Please provide a date in"
            ." the format (${expectedFormat}) or use one of the following special"
            ." values (${specialFormats})",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}