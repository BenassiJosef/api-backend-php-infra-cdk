<?php


namespace App\Package\Segments\Operators\Comparisons;

use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonSignatureException;
use JsonSerializable;

/**
 * Class ComparisonInput
 * @package App\Package\Segments\Operators\Comparisons
 */
class ComparisonInput implements JsonSerializable
{
    public static $allowedSignatures = [
        ['field', 'comparison', 'value'],
        ['field', 'comparison', 'value', 'mode']
    ];

    /**
     * @param array $data
     * @return static
     * @throws InvalidComparisonSignatureException
     */
    public static function fromArray(array $data): self
    {
        self::validateSignature($data);
        return new self(
            $data['field'],
            $data['comparison'],
            $data['value'],
            $data['mode'] ?? null
        );
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function isValidSignature(array $data): bool
    {
        $keys = array_keys($data);
        sort($keys);
        foreach (self::$allowedSignatures as $allowedSignature) {
            if (count($keys) !== count($allowedSignature)) {
                continue;
            }
            sort($allowedSignature);
            if (count(array_diff($keys, $allowedSignature)) !== 0) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * @param array[] $data
     * @throws InvalidComparisonSignatureException
     */
    private static function validateSignature(array $data): void
    {
        if (self::isValidSignature($data)) {
            return;
        }
        throw new InvalidComparisonSignatureException(array_keys($data), self::$allowedSignatures);
    }

    /**
     * @var string $fieldName
     */
    private $fieldName;

    /**
     * @var string
     */
    private $comparison;

    /**
     * @var string | null
     */
    private $comparisonMode;

    /**
     * @var string | int | bool
     */
    private $value;

    /**
     * ComparisonInput constructor.
     * @param string $fieldName
     * @param string $comparison
     * @param bool|int|string $value
     * @param string|null $comparisonMode
     */
    public function __construct(
        string $fieldName,
        string $comparison,
        $value,
        ?string $comparisonMode = null
    ) {
        $this->fieldName      = $fieldName;
        $this->comparison     = $comparison;
        $this->comparisonMode = $comparisonMode;
        $this->value          = $value;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getComparison(): string
    {
        return $this->comparison;
    }

    /**
     * @return string|null
     */
    public function getComparisonMode(): ?string
    {
        return $this->comparisonMode;
    }

    /**
     * @return bool|int|string
     */
    public function getValue()
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
        return from(
            [
                'field'      => $this->fieldName,
                'comparison' => $this->comparison,
                'mode'       => $this->comparisonMode,
                'value'      => $this->value,
            ]
        )
            ->where(
                function ($value): bool {
                    return $value !== null;
                }
            )
            ->toArray();
    }
}