<?php
/**
 * Created by jamieaitken on 01/02/2018 at 14:48
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Schedule;

use App\Utils\Strings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * LocationSchedule
 *
 * @ORM\Table(name="network_schedule")
 * @ORM\Entity
 */
class LocationSchedule
{

    public function __construct()
    {
        $this->enabled   = false;
        $this->updatedAt = new \DateTime();
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
     * @var boolean
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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