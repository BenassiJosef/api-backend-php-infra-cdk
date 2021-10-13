<?php

namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Field;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidModifierException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorForTypeException;
use App\Package\Segments\Values\Value;

/**
 * Class LikeComparison
 * @package App\Package\Segments\Operators
 */
class LikeComparison implements Comparison, ModifiedComparison
{
    const LIKE     = 'like';
    const NOT_LIKE = 'not-like';

    /**
     * @var bool[]
     */
    public static $allowedOperators = [
        self::LIKE     => true,
        self::NOT_LIKE => true,
    ];

    /**
     * @param string $operator
     * @throws InvalidOperatorException
     */
    public static function validateOperator(string $operator)
    {
        if (!array_key_exists($operator, self::$allowedOperators)) {
            throw new InvalidOperatorException($operator);
        }
    }

    const MODIFIER_CONTAINS    = 'contains';
    const MODIFIER_STARTS_WITH = 'starts-with';
    const MODIFIER_ENDS_WITH   = 'ends-with';

    /**
     * @var bool[]
     */
    public static $allowedModifiers = [
        self::MODIFIER_CONTAINS    => true,
        self::MODIFIER_STARTS_WITH => true,
        self::MODIFIER_ENDS_WITH   => true,
    ];

    /**
     * @param string $modifier
     * @throws InvalidModifierException
     */
    public static function validateModifier(string $modifier)
    {
        if (!array_key_exists($modifier, self::$allowedModifiers)) {
            throw new InvalidModifierException($modifier);
        }
    }

    /**
     * @var Field $field
     */
    private $field;

    /**
     * @var string $operator
     */
    private $operator;

    /**
     * @var string $modifier
     */
    private $modifier;

    /**
     * @var Value
     */
    private $value;

    /**
     * LikeComparison constructor.
     * @param Field $field
     * @param string $operator
     * @param string $modifier
     * @param Value $value
     * @throws InvalidModifierException
     * @throws InvalidOperatorException
     * @throws InvalidOperatorForTypeException
     */
    public function __construct(
        Field $field,
        string $operator,
        string $modifier,
        Value $value
    ) {
        $operator = strtolower($operator);
        $modifier = strtolower($modifier);
        self::validateOperator($operator);
        self::validateModifier($modifier);
        if ($field->getType() !== Field::TYPE_STRING) {
            throw new InvalidOperatorForTypeException($field->getType(), $operator);
        }
        $this->field    = $field;
        $this->operator = $operator;
        $this->modifier = $modifier;
        $this->value    = $value;
    }

    /**
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getModifier(): string
    {
        return $this->modifier;
    }

    /**
     * @return Value
     */
    public function getValue(): Value
    {
        return $this->value;
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
            'field'      => $this->field,
            'comparison' => $this->operator,
            'mode'       => $this->modifier,
            'value'      => $this->value,
        ];
    }
}