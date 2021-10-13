<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingCallback
 *
 * @ORM\Table(name="marketing_callback", indexes={
 *     @ORM\Index(name="eventTo", columns={"eventTo"}),
 *     @ORM\Index(name="messageId", columns={"messageId"})
 * })
 * @ORM\Entity
 */
class MarketingCallback
{

    public function __construct(string $type, string $messageId, string $eventId, string $eventTo, int $timestamp, string $event,
                                string $smptId, string $category, $jsonData, string $serial, int $profileId)
    {
        $this->type      = $type;
        $this->messageid = $messageId;
        $this->eventid   = $eventId;
        $this->eventto   = $eventTo;
        $this->timestamp = $timestamp;
        $this->event     = $event;
        $this->smptid    = $smptId;
        $this->category  = $category;
        $this->data      = $jsonData;
        $this->serial    = $serial;
        $this->profileid = $profileId;
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
     * @ORM\Column(name="messageId", type="string", length=58, nullable=true)
     */
    private $messageid;

    /**
     * @var string
     *
     * @ORM\Column(name="eventId", type="string", length=36, nullable=true)
     */
    private $eventid;

    /**
     * @var string
     *
     * @ORM\Column(name="eventTo", type="string", length=50, nullable=true)
     */
    private $eventto;

    /**
     * @var integer
     *
     * @ORM\Column(name="timestamp", type="integer", nullable=true)
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=14, nullable=true)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="smptId", type="string", length=50, nullable=true)
     */
    private $smptid;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=20, nullable=true)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="json_array", length=65535, nullable=true)
     */
    private $data;

    /**
     * @var integer
     *
     * @ORM\Column(name="profileId", type="integer", nullable=true)
     */
    private $profileid;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string", nullable=true)
     */
    private $serial;

    /**
     * Get array copy of object
     *
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

