<?php

namespace App\Package\Segments;

use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;

/**
 * Class PersistentSegmentInput
 * @package App\Package\Segments
 */
class PersistentSegmentInput implements JsonSerializable
{

    /**
     * @param Request $request
     * @return static
     * @throws Exceptions\InvalidSegmentInputException
     * @throws Exceptions\UnknownNodeException
     * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
     * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
     */
    public static function fromRequest(Request $request): self
    {
        return self::fromArray(
            $request->getParsedBody()
        );
    }

    /**
     * @param array $data
     * @return static
     * @throws Exceptions\InvalidSegmentInputException
     * @throws Exceptions\UnknownNodeException
     * @throws Operators\Comparisons\Exceptions\InvalidComparisonSignatureException
     * @throws Operators\Logic\Exceptions\InvalidLogicInputSignatureException
     */
    public static function fromArray(array $data): self
    {
        $segmentInput = null;
        if (array_key_exists('segment', $data) && $data['segment'] !== null) {
            $segmentInput = SegmentInput::fromArray($data['segment']);
        }
        return new self(
            Uuid::fromString($data['version'] ?? Uuid::NIL),
            $data['name'] ?? null,
            $segmentInput
        );
    }

    /**
     * @var UuidInterface $version
     */
    private $version;

    /**
     * @var string | null $name
     */
    private $name;

    /**
     * @var SegmentInput | null $segment
     */
    private $segment;

    /**
     * PersistentSegmentInput constructor.
     * @param UuidInterface $version
     * @param string | null $name
     * @param SegmentInput | null $segment
     */
    public function __construct(
        UuidInterface $version,
        ?string $name,
        ?SegmentInput $segment
    ) {
        $this->version = $version;
        $this->name    = $name;
        $this->segment = $segment;
    }

    /**
     * @return UuidInterface
     */
    public function getVersion(): UuidInterface
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return SegmentInput
     */
    public function getSegment(): ?SegmentInput
    {
        return $this->segment;
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
            'version' => $this->version->toString(),
            'name'    => $this->name,
            'segment' => $this->segment,
        ];
    }
}