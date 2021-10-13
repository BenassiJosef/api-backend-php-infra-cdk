<?php

namespace App\Package\Segments\Database\Parameters;

use App\Package\Segments\Values\Arguments\Argument;

/**
 * Class ArgumentMapper
 * @package App\Package\Segments\Database\Parameters
 */
class ArgumentMapper implements ArgumentCanonicaliser
{
    /**
     * @var Argument[] $arguments
     */
    private $arguments;

    /**
     * ArgumentMapper constructor.
     * @param Argument[] $arguments
     */
    public function __construct(Argument ...$arguments)
    {
        $this->arguments = [];
        foreach ($arguments as $argument) {
            $this->canonicalise($argument);
        }
    }

    /**
     * @param Argument $argument
     * @return Argument
     */
    public function canonicalise(Argument $argument): Argument
    {
        if (!$this->hasArgument($argument)) {
            return $this->store($argument);
        }
        $argument = IncrementingSuffixArgument::wrap($argument);
        while ($this->hasArgument($argument)) {
            $argument->increment();
        }
        return $this->store($argument);
    }

    /**
     * @return Argument[]
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param Argument $argument
     * @return Argument
     */
    private function store(Argument $argument): Argument
    {
        $this->arguments[$argument->getName()] = $argument;
        return $argument;
    }

    /**
     * @param Argument $argument
     * @return bool
     */
    public function hasArgument(Argument $argument): bool
    {
        return array_key_exists($argument->getName(), $this->arguments);
    }

    /**
     * @return array
     */
    public function parameters(): array
    {
        return from($this->arguments)
            ->select(
                function (Argument $argument) {
                    return $argument->getValue();
                },
                function (Argument $argument) {
                    return $argument->getName();
                }
            )
            ->toArray();
    }

    /**
     * @return $this
     */
    public function copy(): self
    {
        $mapper = new self();
        $mapper->arguments = $this->arguments;
        return $mapper;
    }
}