<?php
/**
 * Created by jamieaitken on 11/06/2018 at 13:56
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Members;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * CustomerPricing
 *
 * @ORM\Table(name="customer_pricing")
 * @ORM\Entity
 */
class CustomerPricing
{

    public function __construct(Organization $customerOrganisation, int $starter = 5000, int $allIn = 10000)
    {
        $this->uid            = Uuid::uuid1();
        $this->organizationId = $customerOrganisation->getId();
        $this->organization   = $customerOrganisation;
        $this->organizationId = $customerOrganisation->getId();
        $this->starter        = $starter;
        $this->allIn          = $allIn;
    }

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     * @ORM\Id
     */
    private $uid;

    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization", inversedBy="access", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var integer
     * @ORM\Column(name="lite", type="integer")
     */
    private $lite = 5000;

    /**
     * @var integer
     * @ORM\Column(name="medium", type="integer")
     */
    private $medium = 6000;

    /**
     * @var integer
     * @ORM\Column(name="premium", type="integer")
     */
    private $premium = 7000;

    /**
     * @var integer
     * @ORM\Column(name="starter", type="integer")
     */
    private $starter = 5000;

    /**
     * @var integer
     * @ORM\Column(name="allIn", type="integer")
     */
    private $allIn = 10000;

    /**
     * @var integer
     * @ORM\Column(name="reviews", type="integer")
     */
    private $reviews = 3000;

    /**
     * @var integer
     * @ORM\Column(name="reviewsCreditAllocation", type="integer")
     */
    private $reviewsCreditAllocation = 1000;

    /**
     * @var integer
     * @ORM\Column(name="unifiApHosting", type="integer")
     */
    private $unifiApHosting = 250;

    /**
     * @var integer
     * @ORM\Column(name="unifiHosting", type="integer")
     */
    private $unifiHosting = 9000;

    /**
     * @var integer
     * @ORM\Column(name="contentFilter", type="integer")
     */
    private $contentFilter = 1500;

    /**
     * @var integer
     * @ORM\Column(name="marketingAutomation", type="integer")
     */
    private $marketingAutomation = 2500;

    /**
     * @var integer
     * @ORM\Column(name="marketingAutomationCreditAllocation", type="integer")
     */

    private $marketingAutomationCreditAllocation = 250;

    /**
     * @var integer
     * @ORM\Column(name="customIntegration", type="integer")
     */
    private $customIntegration = 3000;

    /**
     * @var integer
     * @ORM\Column(name="stories", type="integer")
     */
    private $stories = 1500;

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

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return int
     */
    public function getLite(): int
    {
        return $this->lite;
    }

    /**
     * @return int
     */
    public function getMedium(): int
    {
        return $this->medium;
    }

    /**
     * @return int
     */
    public function getPremium(): int
    {
        return $this->premium;
    }

    /**
     * @return int
     */
    public function getStarter(): int
    {
        return $this->starter;
    }

    /**
     * @return int
     */
    public function getAllIn(): int
    {
        return $this->allIn;
    }

    /**
     * @return int
     */
    public function getReviews(): int
    {
        return $this->reviews;
    }

    /**
     * @return int
     */
    public function getReviewsCreditAllocation(): int
    {
        return $this->reviewsCreditAllocation;
    }

    /**
     * @return int
     */
    public function getUnifiApHosting(): int
    {
        return $this->unifiApHosting;
    }

    /**
     * @return int
     */
    public function getUnifiHosting(): int
    {
        return $this->unifiHosting;
    }

    /**
     * @return int
     */
    public function getContentFilter(): int
    {
        return $this->contentFilter;
    }

    /**
     * @return int
     */
    public function getMarketingAutomation(): int
    {
        return $this->marketingAutomation;
    }

    /**
     * @return int
     */
    public function getMarketingAutomationCreditAllocation(): int
    {
        return $this->marketingAutomationCreditAllocation;
    }

    /**
     * @return int
     */
    public function getCustomIntegration(): int
    {
        return $this->customIntegration;
    }

    /**
     * @return int
     */
    public function getStories(): int
    {
        return $this->stories;
    }


}