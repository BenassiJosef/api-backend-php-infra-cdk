<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkWhitelist
 *
 * @ORM\Table(name="network_whitelist")
 * @ORM\Entity
 */
class NetworkWhitelist
{

    public function __construct($alias, $mac, $serial)
    {
        $this->alias   = $alias;
        $this->mac     = $mac;
        $this->serial  = $serial;
        $this->deleted = false;
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
     * @ORM\Column(name="alias", type="string", length=50, nullable=true)
     */
    private $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=true)
     */
    private $mac;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted = '0';

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

    public function getAll() {
        return 'hello';
    }

}

