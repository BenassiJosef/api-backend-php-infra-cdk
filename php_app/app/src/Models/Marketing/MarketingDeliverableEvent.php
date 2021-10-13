<?php
/**
 * Created by jamieaitken on 2019-06-13 at 12:25
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingDeliverable
 * @ORM\Table(name="marketing_deliverability_events")
 * @ORM\Entity
 */
class MarketingDeliverableEvent
{

    public function __construct(string $marketingDeliverableId, string $event, int $timestamp, string $eventSpecificInfo)
    {
        $this->marketingDeliverableId = $marketingDeliverableId;
        $this->event                  = $event;
        $this->timestamp              = $timestamp;
        $this->eventSpecificInfo      = $eventSpecificInfo;
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * var string
     * @ORM\Column(name="marketingDeliverableId", type="string")
     */
    private $marketingDeliverableId;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string")
     */
    private $event;

    /**
     * @var integer
     * @ORM\Column(name="timestamp", type="integer")
     */
    private $timestamp;

    /**
     * @var string
     * @ORM\Column(name="eventSpecificInfo", type="string")
     */
    private $eventSpecificInfo;

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