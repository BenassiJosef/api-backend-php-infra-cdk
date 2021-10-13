<?php

namespace App\Models;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingEvents
 *
 * @ORM\Table(name="marketing_event", indexes={@ORM\Index(name="profileId", columns={"profileId"}), @ORM\Index(name="serial", columns={"serial"})})
 * @ORM\Entity
 */
class MarketingEvents
{

    public function __construct($type, $eventto, $profileId, $serial, $eventId, $campaignId, $optOutCode)
    {
        $this->type       = $type;
        $this->eventto    = $eventto;
        $this->profileId  = $profileId;
        $this->serial     = $serial;
        $this->eventId    = $eventId;
        $this->campaignId = $campaignId;
        $this->optOutCode = $optOutCode;
        $this->timestamp  = new \DateTime();
        $mx               = new _Mixpanel();
        $mx->identify($serial)->track('marketing_event', [
            'type'       => $type,
            'eventto'    => $eventto,
            'profileId'  => $profileId,
            'serial'     => $serial,
            'eventId'    => $eventId,
            'campaignId' => $campaignId
        ]);

    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=5, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="eventTo", type="string", length=100, nullable=true)
     */
    private $eventto;

    /**
     * @var integer
     *
     * @ORM\Column(name="profileId", type="integer", nullable=true)
     */
    private $profileId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="eventId", type="string", length=36, nullable=true)
     */
    private $eventId;

    /**
     * @var string
     *
     * @ORM\Column(name="campaignId", type="string", length=36, nullable=true)
     */
    private $campaignId;

    /**
     * @var string
     * @ORM\Column(name="optOutCode", type="string")
     */
    private $optOutCode;

    /**
     * @var float
     * @ORM\Column(name="spendPerHead", type="float")
     */
    private $spendPerHead;

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
