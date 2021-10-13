<?php

namespace App\Package\Segments\Values\Arguments;

/**
 * Class UnWrapPreventingArgument
 * @package App\Package\Segments\Values\Arguments
 */
class UnWrapPreventingArgument implements Argument
{
    /**
     * @var Argument $base
     */
    private $base;

    /**
     * UnWrapPreventingArgument constructor.
     * @param Argument $base
     */
    public function __construct(Argument $base)
    {
        $this->base = $base;
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
        return $this->getValue();
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        return [$this];
    }

    /**
     * @return string | int | bool
     */
    public function rawValue()
    {
        return $this->base->rawValue();
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
        return $this->base->jsonSerialize();
    }


}