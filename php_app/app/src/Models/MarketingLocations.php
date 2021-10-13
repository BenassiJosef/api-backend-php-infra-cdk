<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingLocations
 *
 * @ORM\Table(name="marketing_campaign_serials")
 * @ORM\Entity
 */
class MarketingLocations
{

    public function __construct($campaignId, $serial)
    {
        $this->campaignId = $campaignId;
        $this->serial     = $serial;
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
     *
     * @ORM\Column(name="campaignId", type="string", length=36, nullable=true)
     */
    private $campaignId;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

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

