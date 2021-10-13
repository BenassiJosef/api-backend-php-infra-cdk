<?php


namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Values\Value;
use App\Package\Segments\Fields\Field;

/**
 * Class StandardComparison
 * @package App\Package\Segments\Operators\Comparisons
 */
class StandardComparison implements Comparison
{
    const EQ = '==';

    const NEQ = '<>';

    const GT = '>';

    const GTE = '>=';

    const LT = '<';

    const LTE = '<=';

    /**
     * @var bool[]
     */
    public static $allowedOperatorsForType = [
        Field::TYPE_BOOLEAN  => [
            self::EQ  => true,
            self::NEQ => true,
        ],
        Field::TYPE_DATETIME => [
            self::EQ  => true,
            self::NEQ => true,
            self::GT  => true,
            self::GTE => true,
            self::LT  => true,
            self::LTE => true,
        ],
        Field::TYPE_INTEGER  => [
            self::EQ  => true,
            self::NEQ => true,
            self::GT  => true,
            self::GTE => true,
            self::LT  => true,
            self::LTE => true,
        ],
        Field::TYPE_STRING   => [
            self::EQ  => true,
            self::NEQ => true,
        ],
        Field::TYPE_YEARDATE => [
            self::EQ  => true,
            self::NEQ => true,
        ],
    ];

    /**
     * @param string $type
     * @param string $operator
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
     */
    private static function validateFieldTypeAndOperator(string $type, string $operator): void
    {
        if (!array_key_exists($type, self::$allowedOperatorsForType)) {
            throw new InvalidTypeException($type, array_keys(self::$allowedOperatorsForType));
        }
        $allowedOperators = self::$allowedOperatorsForType[$type];
        if (!array_key_exists($operator, $allowedOperators)) {
            throw new InvalidOperatorException($operator, $type);
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
     * @var Value
     */
    private $value;

    /**
     * StandardComparison constructor.
     * @param Field $field
     * @param string $operator
     * @param Value $value
     * @throws InvalidOperatorException
     * @throws InvalidTypeException
     */
    public function __construct(
        Field $field,
        string $operator,
        Value $value
    ) {
        self::validateFieldTypeAndOperator($field->getType(), $operator);
        $this->field    = $field;
        $this->operator = $operator;
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
            'field'    => $this->field,
            'comparison' => $this->operator,
            'value'    => $this->value,
        ];
    }
}