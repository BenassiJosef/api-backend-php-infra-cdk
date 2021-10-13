<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/03/2017
 * Time: 14:20
 */

namespace App\Models\Locations\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CampaignEventOptions
 * @package App\Models\Locations\Marketing
 *
 * @ORM\Table(name="marketing_campaign_events_options")
 * @ORM\Entity
 */


class CampaignEventOptions
{

    public function __construct($eventId, $event, $operand, $value)
    {
        $this->eventId = $eventId;
        $this->event   = $event;
        $this->operand = $operand;
        $this->value   = $value;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';

    /**
     * @var string
     * @ORM\Column(name="eventId", type="string")
     */
    private $eventId;

    /**
     * @var string
     * @ORM\Column(name="event", type="string")
     */
    private $event;

    /**
     * @var string
     * @ORM\Column(name="operand", type="string")
     */
    private $operand;

    /**
     * @var string
     * @ORM\Column(name="value", type="string")
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