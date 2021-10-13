<?php


namespace App\Package\Segments\Operators\Logic;

use App\Package\Segments\Fields\Field;
use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Operators\Comparisons\ModifiedComparison;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicalOperatorException;
use App\Package\Segments\Operators\Logic\Exceptions\NotEnoughNodesException;
use JsonSerializable;

/**
 * Class LogicalOperator
 * @package App\Package\Segments\Operators\Logic
 */
class LogicalOperator implements JsonSerializable
{
    const OPERATOR_AND = 'and';

    const OPERATOR_OR = 'or';

    /**
     * @var bool[]
     */
    public static $allowedOperators = [
        self::OPERATOR_AND => true,
        self::OPERATOR_OR  => true
    ];

    /**
     * @param string $operator
     * @throws InvalidLogicalOperatorException
     */
    public static function validateOperator(string $operator): void
    {
        if (!array_key_exists($operator, self::$allowedOperators)) {
            throw new InvalidLogicalOperatorException($operator, array_keys(self::$allowedOperators));
        }
    }

    /**
     * @var string $operator
     */
    private $operator;

    /**
     * @var Container[] $nodes
     */
    private $nodes;

    /**
     * LogicalOperator constructor.
     * @param string $operator
     * @param Container[] $nodes
     * @throws InvalidLogicalOperatorException
     * @throws NotEnoughNodesException
     */
    public function __construct(string $operator, array $nodes = [])
    {
        $operator = strtolower($operator);
        self::validateOperator($operator);
        if (count($nodes) < 2) {
            throw new NotEnoughNodesException($operator, $nodes);
        }
        $this->operator = $operator;
        $this->nodes    = $nodes;
    }

    /**
     * @return bool[]
     */
    public static function getAllowedOperators(): array
    {
        return self::$allowedOperators;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return Container[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        $fields = [];
        foreach ($this->nodes as $container) {
            $fields = array_merge($container->fields(), $fields);
        }
        return $fields;
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