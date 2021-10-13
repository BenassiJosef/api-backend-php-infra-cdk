<?php

namespace App\Package\Segments\Values\Arguments;

/**
 * Class PrefixedArgument
 * @package App\Package\Segments\Values\Arguments
 */
class PrefixedArgument implements Argument, UnWrappable
{
    /**
     * @var string $prefix
     */
    private $prefix;

    /**
     * @var Argument $argument
     */
    private $argument;

    /**
     * @var bool $unWrapToSelf
     */
    private $unWrapToSelf;

    /**
     * PrefixedArgument constructor.
     * @param string $prefix
     * @param Argument $argument
     * @param bool $unWrapToSelf
     */
    public function __construct(
        string $prefix,
        Argument $argument,
        bool $unWrapToSelf = false
    ) {
        $this->prefix       = $prefix;
        $this->argument     = $argument;
        $this->unWrapToSelf = $unWrapToSelf;
    }

    /**
     * @return Argument
     */
    public function unWrap(): Argument
    {
        if ($this->unWrapToSelf) {
            return new UnWrapPreventingArgument($this);
        }
        if ($this->argument instanceof UnWrappable) {
            return $this->argument->unWrap();
        }
        return $this->argument;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->prefix . ucfirst($this->argument->getName());
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->argument->getValue();
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        if ($this->unWrapToSelf) {
            return [new UnWrapPreventingArgument($this)];
        }
        return [$this];
    }

    /**
     * @return string | int | bool
     */
    public function rawValue()
    {
        return $this->argument->rawValue();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return $this->argument->jsonSerialize();
    }


}