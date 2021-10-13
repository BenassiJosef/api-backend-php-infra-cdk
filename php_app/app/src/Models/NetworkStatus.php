<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkStatus
 *
 * @ORM\Table(name="network_status", indexes={@ORM\Index(name="serial", columns={"serial"})})
 * @ORM\Entity
 */
class NetworkStatus
{
    public function __construct($serial)
    {
        $this->serial    = $serial;
        $this->status    = true;
        $this->timestamp = new \DateTime();
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
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="ip", type="integer", nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="master_site", type="string", length=12, nullable=true)
     */
    private $master_site;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="cpu", type="integer", nullable=true)
     */
    private $cpu;

    /**
     * @var string
     *
     * @ORM\Column(name="model", type="string", length=30, nullable=true)
     */
    private $model;

    /**
     * @var boolean
     *
     * @ORM\Column(name="master", type="boolean", nullable=true)
     */
    private $master;

    /**
     * @var boolean
     *
     * @ORM\Column(name="waitingConfig", type="boolean", nullable=true)
     */
    private $waitingConfig;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cpu_warning", type="boolean", nullable=true)
     */
    private $cpu_warning;

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

