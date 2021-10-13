<?php

namespace App\Package\Time;

use DateTime;
use DateTimeImmutable;
use Exception;
use Google\Cloud\Core\TimeTrait;

/**
 * Class TimestampParser
 * @package App\Package\Time
 */
class TimestampParser
{
    use TimeTrait;

    /**
     * @param string $timestamp
     * @return DateTimeImmutable
     * @throws Exception
     */
    public static function parseTimestamp(string $timestamp): DateTimeImmutable
    {
        return (new self())->parse($timestamp);
    }

    /**
     * @param string $timestamp
     * @return DateTimeImmutable
     * @throws Exception
     */
    public function parse(string $timestamp): DateTimeImmutable
    {
        [$timestamp, ] = $this->parseTimeString($timestamp);
        return $timestamp;
    }
}