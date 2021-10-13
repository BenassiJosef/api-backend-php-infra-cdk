<?php

namespace App\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * MarketingEvents
 *
 * @ORM\Table(name="marketing_campaign_events")
 * @ORM\Entity
 */
class MarketingCampaignEvents
{

    public function __construct(string $name, Organization $organization, float $spendPerHead)
    {
        $this->name         = $name;
        $this->admin        = $organization->getOwnerId();
        $this->organizationId = $organization->getId();
        $this->spendPerHead = $spendPerHead;
        $this->created      = new DateTime();
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
     * @var DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="moderator", type="string", length=36, nullable=true)
     */
    private $moderator;

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=36, nullable=true)
     */
    private $admin;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

    /**
     * @var float
     * @ORM\Column(name="spendPerHead", type="float")
     */
    private $spendPerHead;


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

