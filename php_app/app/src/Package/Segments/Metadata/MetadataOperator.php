<?php

namespace App\Package\Segments\Metadata;

/**
 * Class MetadataOperator
 * @package App\Package\Segments\Metadata
 */
class MetadataOperator implements \JsonSerializable
{
    /**
     * @var string $operator
     */
    private $operator;

    /**
     * @var string[] $modes
     */
    private $modes;

    /**
     * MetadataOperator constructor.
     * @param string $operator
     * @param string[] $modes
     */
    public function __construct(
        string $operator,
        array $modes = []
    ) {
        $this->operator = $operator;
        $this->modes    = $modes;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string[]
     */
    public function getModes(): array
    {
        return $this->modes;
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
        $output = [
            'operator' => $this->operator,
        ];

        if (count($this->modes) > 0) {
            $output['modes'] = $this->modes;
        }
        return $output;
    }
}