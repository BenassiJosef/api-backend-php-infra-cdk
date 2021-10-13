<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * NodeDetails
 *
 * @ORM\Table(name="node_details")
 * @ORM\Entity
 */
class NodeDetails
{

    function __construct($serial, $alias, $mac, $wanIp)
    {
        $this->serial    = $serial;
        $this->alias     = $alias;
        $this->mac       = $mac;
        $this->wanIp     = $wanIp;
        $this->deleted   = false;
        $this->status    = 1;
        $this->createdAt = new \DateTime();
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
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=50, nullable=true)
     */
    private $alias;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=true)
     */
    private $mac;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=true)
     */
    private $ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="port", type="integer", nullable=true)
     */
    private $port;

    /**
     * @var float
     *
     * @ORM\Column(name="lat", type="float", precision=9, scale=6, nullable=true)
     */
    private $lat;

    /**
     * @var float
     *
     * @ORM\Column(name="lng", type="float", precision=9, scale=6, nullable=true)
     */
    private $lng;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastping", type="datetime", nullable=true)
     */
    private $lastping;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="up_count", type="integer", nullable=true)
     */
    private $upCount = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="down_count", type="integer", nullable=true)
     */
    private $downCount = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="alerts", type="boolean", nullable=true)
     */
    private $alerts = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted = '0';


    /**
     * @var string
     * @ORM\Column(name="wan_ip", type="string")
     */
    private $wanIp;

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

