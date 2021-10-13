<?php
/**
 * Created by jamieaitken on 02/02/2018 at 09:55
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Schedule;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationScheduleTime
 *
 * @ORM\Table(name="network_schedule_time")
 * @ORM\Entity
 */
class LocationScheduleTime
{

    public function __construct(string $dayId)
    {
        $this->dayId     = $dayId;
        $this->close     = 1105;
        $this->open      = 305;
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
     * @var string
     *
     * @ORM\Column(name="dayId", type="string", length=36, nullable=false)
     */
    private $dayId;

    /**
     * @var integer
     * @ORM\Column(name="open", type="integer")
     */
    private $open;

    /**
     * @var integer
     * @ORM\Column(name="close", type="integer")
     */
    private $close;

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