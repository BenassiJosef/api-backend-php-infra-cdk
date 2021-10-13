<?php


namespace App\Package\Loyalty\Reward;


use DateTime;
use Ramsey\Uuid\UuidInterface;

interface Reward
{
    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface;

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * @return int|null
     */
    public function getAmount(): ?int;

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime;
}