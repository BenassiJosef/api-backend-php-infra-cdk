<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingEventsOptions
 *
 * @ORM\Table(name="marketing_campaign_events_options")
 * @ORM\Entity
 */
class MarketingEventOptions
{

    public function __construct($eventId, $event, $operand, $value, $position)
    {
        $this->eventId = $eventId;
        $this->operand = $operand;
        $this->value   = $value;
        $this->event   = $event;
        $this->position   = $position;
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
     * @ORM\Column(name="eventId", type="string", length=36, nullable=true)
     */
    private $eventId;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=16, nullable=true)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="operand", type="string", length=10, nullable=true)
     */
    private $operand;

    /**
     * @var string
     *
     * @ORM\Column(name="conditions", type="string", length=3, nullable=true)
     */
    private $condition;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", length=3, nullable=true)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=3, nullable=true)
     */
    private $value;

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

