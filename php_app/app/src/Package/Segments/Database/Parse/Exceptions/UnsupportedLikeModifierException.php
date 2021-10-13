<?php


namespace App\Package\Segments\Database\Parse\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Operators\Comparisons\LikeComparison;

/**
 * Class UnsupportedLikeModifierException
 * @package App\Package\Segments\Database\Parse\Exceptions
 */
class UnsupportedLikeModifierException extends BaseException
{
    /**
     * UnsupportedLikeModifierException constructor.
     * @param string $modifier
     * @param string[]|null $supportedModifiers
     */
    public function __construct(string $modifier, ?array $supportedModifiers = null)
    {
        if ($supportedModifiers === null) {
            $supportedModifiers = array_keys(LikeComparison::$allowedModifiers);
        }
        $supportedModifiersString = implode(', ', $supportedModifiers);
        parent::__construct(
            "(${modifier}) is not a supported modifier for like or not likes (${supportedModifiersString})"
        );
    }
}