<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailAlerts
 *
 * @ORM\Table(name="email_alerts")
 * @ORM\Entity
 */
class EmailAlerts
{

    public function __construct($serial, $list, $types)
    {
        $this->serial    = $serial;
        $this->list      = $list;
        $this->types     = $types;
        $this->enabled   = true;
        $this->createdAt = new \DateTime();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial = '';

    /**
     * @var string
     *
     * @ORM\Column(name="list", type="json_array", length=65535, nullable=true)
     */
    private $list;

    /**
     * @var string
     *
     * @ORM\Column(name="types", type="json_array", length=65535, nullable=true)
     */
    private $types;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var boolean
     * @ORM\Column(name="enabled", type="boolean")
     */

    private $enabled;

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

