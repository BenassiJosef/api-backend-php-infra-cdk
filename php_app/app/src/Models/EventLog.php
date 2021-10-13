<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventLog
 *
 * @ORM\Table(name="event_log")
 * @ORM\Entity
 */
class EventLog
{
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
     * @ORM\Column(name="message", type="string", length=254, nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var integer
     *
     * @ORM\Column(name="code", type="integer", nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=34, nullable=true)
     */
    private $admin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    private $timestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="meta", type="string", length=20, nullable=true)
     */
    private $meta;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=30, nullable=true)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="param", type="string", length=30, nullable=true)
     */
    private $param;

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

