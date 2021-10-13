<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/01/2017
 * Time: 14:03
 */

namespace App\Models;

use JsonSerializable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plans
 *
 * @ORM\Table(name="location_plans")
 * @ORM\Entity
 */
class LocationPlan implements JsonSerializable
{

    public function __construct($adminId, $name, $deviceAllowance, $duration, $cost)
    {
        $this->adminId         = $adminId;
        $this->name            = $name;
        $this->deviceAllowance = $deviceAllowance;
        $this->duration        = $duration;
        $this->cost            = $cost;
        $this->isDeleted       = 0;
        $this->createdAt       = new \DateTime();
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
     * @ORM\Column(name="adminId", type="string")
     */

    private $adminId;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */

    private $name;

    /**
     * @var integer
     * @ORM\Column(name="deviceAllowance", type="integer")
     */

    private $deviceAllowance;

    /**
     * @var integer
     * @ORM\Column(name="duration", type="integer")
     */

    private $duration;

    /**
     * @var integer
     * @ORM\Column(name="cost", type="integer")
     */

    private $cost;

    /**
     * @var boolean
     * @ORM\Column(name="isDeleted", type="boolean")
     */

    private $isDeleted;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */

    private $createdAt;

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

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'device_allowance' => $this->deviceAllowance,
            'duration' => $this->duration,
            'cost' => $this->cost,
            'created_at' => $this->createdAt
        ];
    }
}
