<?php

namespace App\Package\Segments;

use App\Package\Segments\Database\BaseQueries\BaseQueryFactory;
use App\Package\Segments\Exceptions\InvalidSegmentInputException;
use App\Package\Segments\Exceptions\UnknownNodeException;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Logic\LogicInput;
use App\Package\Segments\Values\DateTime\DateTimeFactory;
use App\Package\Segments\Values\YearDate\YearDateRangeFactory;
use JsonSerializable;

/**
 * Class SegmentInput
 * @package App\Package\Segments
 */
class SegmentInput implements JsonSerializable
{
    private static $requiredKeys = [
        'root'
    ];

    /**
     * @param string $json
     * @return static
     * @throws InvalidSegmentInputException
     * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
     * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
     * @throws UnknownNodeException
     */
    public static function fromJsonString(string $json): self
    {
        return self::fromArray(
            json_decode($json, JSON_OBJECT_AS_ARRAY)
        );
    }

    /**
     * @param array $data
     * @return static
     * @throws InvalidSegmentInputException
     * @throws UnknownNodeException
     * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
     * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
     */
    public static function fromArray(array $data): self
    {
        self::validateInput($data);
        $weekStart     = $data['weekStart'] ?? YearDateRangeFactory::WEEK_START_MONDAY;
        $dateFormat    = $data['dateFormat'] ?? DateTimeFactory::INPUT_FORMAT;
        $baseQueryType = $data['baseQueryType'] ?? BaseQueryFactory::ORGANIZATION_REGISTRATION;
        $rootData      = $data['root'];
        if ($rootData === null) {
            return new self(
                null,
                $weekStart,
                $dateFormat,
                $baseQueryType
            );
        }
        if (ComparisonInput::isValidSignature($rootData)) {
            return new self(
                ComparisonInput::fromArray($rootData),
                $weekStart,
                $dateFormat,
                $baseQueryType
            );
        }
        if (LogicInput::isValidSignature($rootData)) {
            return new self(
                LogicInput::fromArray($rootData),
                $weekStart,
                $dateFormat,
                $baseQueryType
            );
        }
        throw new UnknownNodeException($rootData);
    }

    /**
     * @param array $data
     * @throws InvalidSegmentInputException
     */
    private static function validateInput(array $data): void
    {
        foreach (self::$requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new InvalidSegmentInputException(array_keys($data), self::$requiredKeys);
            }
        }
    }

    /**
     * SegmentInput constructor.
     * @param ComparisonInput|LogicInput $root
     * @param string $weekStart
     * @param string $dateFormat
     * @param string $baseQueryType
     */
    public function __construct(
        $root,
        string $weekStart = YearDateRangeFactory::WEEK_START_MONDAY,
        string $dateFormat = DateTimeFactory::INPUT_FORMAT,
        string $baseQueryType = BaseQueryFactory::ORGANIZATION_REGISTRATION
    ) {
        $this->root          = $root;
        $this->weekStart     = $weekStart;
        $this->dateFormat    = $dateFormat;
        $this->baseQueryType = $baseQueryType;
    }

    /**
     * @var ComparisonInput | LogicInput $root
     */
    private $root;

    /**
     * @var string $weekStart
     */
    private $weekStart;

    /**
     * @var string $dateFormat
     */
    private $dateFormat;

    /**
     * @var string $baseQueryType
     */
    private $baseQueryType;

    /**
     * @return string
     */
    public function getBaseQueryType(): string
    {
        return $this->baseQueryType;
    }

    /**
     * @return string
     */
    public function getWeekStart(): string
    {
        return $this->weekStart;
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * @return ComparisonInput|LogicInput
     */
    public function getRoot()
    {
        return $this->root;
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
            'root' => $this->root,
        ];
    }
}