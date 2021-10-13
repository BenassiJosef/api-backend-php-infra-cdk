<?php

namespace App\Package\Segments\Operators\Logic;

use App\Package\Segments\Exceptions\UnknownNodeException;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicInputSignatureException;
use JsonSerializable;

/**
 * Class LogicInput
 * @package App\Package\Segments\Operators\Logic
 */
class LogicInput implements JsonSerializable
{
    /**
     * @var string[]
     */
    private static $requiredProperties = [
        'operator',
        'nodes'
    ];

    /**
     * @param array $data
     * @return static
     * @throws InvalidLogicInputSignatureException
     */
    public static function fromArray(array $data): self
    {
        self::validateSignature($data);
        $operator = $data['operator'];
        $nodes    = $data['nodes'];
        $parsedNodes = from($nodes)
            ->select(
                function (array $node) {
                    if (self::isValidSignature($node)) {
                        return self::fromArray($node);
                    }
                    if (ComparisonInput::isValidSignature($node)) {
                        return ComparisonInput::fromArray($node);
                    }
                    throw new UnknownNodeException($node);
                }
            )
            ->toArray();
        return new self(
            $operator,
            $parsedNodes
        );
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function isValidSignature(array $data): bool
    {
        if (count($data) !== count(self::$requiredProperties)) {
            return false;
        }
        $validKeys = self::$requiredProperties;
        sort($validKeys);
        $keys = array_keys($data);
        sort($keys);
        if (count(array_diff($keys, $validKeys)) === 0) {
            return true;
        }
        return false;
    }

    /**
     * @param array $data
     * @throws InvalidLogicInputSignatureException
     */
    private static function validateSignature(array $data): void
    {
        if (self::isValidSignature($data)) {
            return;
        }
        throw new InvalidLogicInputSignatureException(array_keys($data), self::$requiredProperties);
    }

    /**
     * @var string $operator
     */
    private $operator;

    /**
     * @var ComparisonInput[] | LogicInput[] $nodes
     */
    private $nodes;

    /**
     * LogicInput constructor.
     * @param string $operator
     * @param array $nodes
     */
    public function __construct(string $operator, array $nodes)
    {
        $this->operator = $operator;
        $this->nodes    = $nodes;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return ComparisonInput[]|LogicInput[]
     */
    public function getNodes()
    {
        return $this->nodes;
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
        return [
            'operator' => $this->operator,
            'nodes'    => $this->nodes,
        ];
    }
}