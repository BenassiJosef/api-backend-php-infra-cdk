<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Parse\Exceptions\UnsupportedLikeModifierException;
use App\Package\Segments\Operators\Comparisons\LikeComparison;
use App\Package\Segments\Values\Arguments\Argument;
use App\Package\Segments\Values\Arguments\UnWrappable;

/**
 * Class ValueFormattingArgument
 * @package App\Package\Segments\Database\Parse
 */
class ValueFormattingArgument implements Argument, UnWrappable
{
    /**
     * @var string[]
     */
    private static $likeComparisonFormatMap = [
        LikeComparison::MODIFIER_CONTAINS    => '%%%s%%',
        LikeComparison::MODIFIER_STARTS_WITH => '%s%%',
        LikeComparison::MODIFIER_ENDS_WITH   => '%%%s'
    ];

    /**
     * @param string $modifier
     * @param Argument $base
     * @return static
     * @throws UnsupportedLikeModifierException
     */
    public static function fromLikeModifier(string $modifier, Argument $base): self
    {
        if (!array_key_exists($modifier, self::$likeComparisonFormatMap)) {
            throw new UnsupportedLikeModifierException($modifier, array_keys(self::$likeComparisonFormatMap));
        }
        return new self(
            $base,
            self::$likeComparisonFormatMap[$modifier]
        );
    }

    /**
     * @var Argument|UnWrappable $base
     */
    private $base;

    /**
     * @var string $format
     */
    private $format;

    /**
     * ValueFormattingArgument constructor.
     * @param Argument|UnWrappable $base
     * @param string $format
     */
    public function __construct(Argument $base, string $format)
    {
        $this->base   = $base;
        $this->format = $format;
    }

    /**
     * @return Argument
     */
    public function unWrap(): Argument
    {
        if ($this->base instanceof UnWrappable) {
            return $this->base->unWrap();
        }
        return $this->base;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->base->getName();
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return sprintf($this->format, $this->base->getValue());
    }

    /**
     * @inheritDoc
     */
    public function rawValue()
    {
        return $this->base->rawValue();
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        return [
            $this
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->base->jsonSerialize();
    }
}