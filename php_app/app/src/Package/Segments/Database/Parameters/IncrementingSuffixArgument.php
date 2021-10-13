<?php

namespace App\Package\Segments\Database\Parameters;

use App\Package\Segments\Values\Arguments\Argument;

/**
 * Class IncrementingSuffixArgument
 * @package App\Package\Segments\Database\Parameters
 */
class IncrementingSuffixArgument implements Argument
{
    /**
     * @param Argument $argument
     * @return static
     */
    public static function wrap(Argument $argument): self
    {
        return new self(
            $argument
        );
    }

    /**
     * @var Argument $baseArgument
     */
    private $baseArgument;

    /**
     * @var int $count
     */
    private $count;

    /**
     * IncrementingSuffixArgument constructor.
     * @param Argument $baseArgument
     */
    public function __construct(Argument $baseArgument)
    {
        $this->baseArgument = $baseArgument;
        $this->count        = 1;
    }


    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        $baseName = $this->baseArgument->getName();
        $count    = $this->count;
        return "${baseName}_${count}";
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->baseArgument->getValue();
    }

    /**
     * @inheritDoc
     */
    public function rawValue()
    {
        return $this->baseArgument->rawValue();
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
     * @return $this
     */
    public function increment(): self
    {
        $this->count++;
        return $this;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->baseArgument->jsonSerialize();
    }
}