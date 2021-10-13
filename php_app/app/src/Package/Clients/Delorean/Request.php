<?php


namespace App\Package\Clients\Delorean;


use DateTime;
use DateTimeImmutable;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Request
 * @package App\Package\Clients\delorean
 */
class Request implements JsonSerializable
{
    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var string $namespace
     */
    private $namespace;

    /**
     * @var DateTimeImmutable $scheduledFor
     */
    private $scheduledFor;

    /**
     * @var Job $job
     */
    private $job;

    /**
     * Request constructor.
     * @param string $namespace
     * @param DateTimeImmutable $scheduledFor
     * @param Job $job
     * @throws Exception
     */
    public function __construct(
        string $namespace,
        DateTimeImmutable $scheduledFor,
        Job $job
    ) {
        $this->id           = Uuid::uuid1();
        $this->namespace    = $namespace;
        $this->scheduledFor = $scheduledFor;
        $this->job          = $job;
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
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
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
            'namespace'     => $this->namespace,
            'scheduled_for' => $this->scheduledFor->format(DATE_RFC3339),
            'job'           => $this->job,
        ];
    }
}