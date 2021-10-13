<?php

namespace App\Package\Clients\Delorean;

use App\Package\Time\TimestampParser;
use DateTime;
use DateTimeImmutable;
use Exception;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Core\TimeTrait;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Class Response
 * @package App\Package\Clients\delorean
 */
class Response implements JsonSerializable
{
    use TimeTrait;

    /**
     * @param array $data
     * @return static
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Uuid::fromString($data['id']),
            $data['type'],
            $data['namespace'],
            TimestampParser::parseTimestamp($data['scheduled_for']),
            TimestampParser::parseTimestamp($data['created_at'])
        );
    }

    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $namespace
     */
    private $namespace;

    /**
     * @var DateTimeImmutable $scheduledFor
     */
    private $scheduledFor;

    /**
     * @var DateTimeImmutable $createdAt
     */
    private $createdAt;

    /**
     * Response constructor.
     * @param UuidInterface $id
     * @param string $type
     * @param string $namespace
     * @param DateTimeImmutable $scheduledFor
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        UuidInterface $id,
        string $type,
        string $namespace,
        DateTimeImmutable $scheduledFor,
        DateTimeImmutable $createdAt
    ) {
        $this->id           = $id;
        $this->type         = $type;
        $this->namespace    = $namespace;
        $this->scheduledFor = $scheduledFor;
        $this->createdAt    = $createdAt;
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getScheduledFor(): DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
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
            'id'            => $this->id->toString(),
            'type'          => $this->type,
            'namespace'     => $this->namespace,
            'scheduled_for' => $this->scheduledFor->format(DATE_RFC3339),
            'created_at'    => $this->scheduledFor->format(DATE_RFC3339),
        ];
    }
}