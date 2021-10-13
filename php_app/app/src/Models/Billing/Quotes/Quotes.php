<?php

/**
 * Created by jamieaitken on 08/02/2019 at 14:49
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Billing\Quotes;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Quotes
 *
 * @ORM\Table(name="quotes")
 * @ORM\Entity
 */
class Quotes
{

    public function __construct(
        string $reseller,
        UuidInterface $resellerOrganizationId,
        string $customer,
        UuidInterface $customerOrganizationId,
        string $description,
        $payload,
        string $hostedPage,
        int $expiresAt
    ) {
        $this->reseller               = $reseller;
        $this->resellerOrganizationId = Uuid::fromString($resellerOrganizationId);
        $this->customer               = $customer;
        $this->customerOrganizationId = Uuid::fromString($customerOrganizationId);
        $this->description            = $description;
        $this->payload                = $payload;
        $this->hostedPage             = $hostedPage;
        $this->createdAt              = new \DateTime();
        $this->updatedAt              = new \DateTime();
        $this->expiresAt              = $expiresAt;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="reseller", type="string")
     */
    private $reseller;

    /**
     * @var UuidInterface $resellerOrganizationId
     * @ORM\Column(name="reseller_organization", type="uuid")
     */
    private $resellerOrganizationId;

    /**
     * @var string
     * @ORM\Column(name="customer", type="string")
     */
    private $customer;

    /**
     * @var UuidInterface $customerOrganizationId
     * @ORM\Column(name="customer_organization", type="uuid")
     */
    private $customerOrganizationId;

    /**
     * @var string
     * @ORM\Column(name="description", type="string")
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="payload", type="json_array")
     */
    private $payload;

    /**
     * @var string
     * @ORM\Column(name="hostedPage", type="string")
     */
    private $hostedPage;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var integer
     * @ORM\Column(name="expiredAt", type="integer")
     */
    private $expiresAt;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Quotes
     */
    public function setId(string $id): Quotes
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getReseller(): string
    {
        return $this->reseller;
    }

    /**
     * @param string $reseller
     * @return Quotes
     */
    public function setReseller(string $reseller): Quotes
    {
        $this->reseller = $reseller;
        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getResellerOrganizationId(): UuidInterface
    {
        return $this->resellerOrganizationId;
    }

    /**
     * @param UuidInterface $resellerOrganizationId
     * @return Quotes
     */
    public function setResellerOrganizationId(UuidInterface $resellerOrganizationId): Quotes
    {
        $this->resellerOrganizationId = $resellerOrganizationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomer(): string
    {
        return $this->customer;
    }

    /**
     * @param string $customer
     * @return Quotes
     */
    public function setCustomer(string $customer): Quotes
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getCustomerOrganizationId(): UuidInterface
    {
        return $this->customerOrganizationId;
    }

    /**
     * @param UuidInterface $customerOrganizationId
     * @return Quotes
     */
    public function setCustomerOrganizationId(UuidInterface $customerOrganizationId): Quotes
    {
        $this->customerOrganizationId = $customerOrganizationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Quotes
     */
    public function setDescription(string $description): Quotes
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     * @return Quotes
     */
    public function setPayload(string $payload): Quotes
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostedPage(): string
    {
        return $this->hostedPage;
    }

    /**
     * @param string $hostedPage
     * @return Quotes
     */
    public function setHostedPage(string $hostedPage): Quotes
    {
        $this->hostedPage = $hostedPage;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Quotes
     */
    public function setCreatedAt(\DateTime $createdAt): Quotes
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Quotes
     */
    public function setUpdatedAt(\DateTime $updatedAt): Quotes
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * @param int $expiresAt
     * @return Quotes
     */
    public function setExpiresAt(int $expiresAt): Quotes
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return array
     */

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
