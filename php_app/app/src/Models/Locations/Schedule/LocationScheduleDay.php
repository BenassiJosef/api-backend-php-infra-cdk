<?php
/**
 * Created by jamieaitken on 01/02/2018 at 15:11
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * LocationScheduleDay
 *
 * @ORM\Table(name="network_schedule_day")
 * @ORM\Entity
 */
class LocationScheduleDay
{

    public function __construct(bool $enabled, string $scheduleId, int $dayNumberOfWeek)
    {
        $this->enabled    = $enabled;
        $this->scheduleId = $scheduleId;
        $this->dayNumber  = $dayNumberOfWeek;
        $this->updatedAt  = new \DateTime();
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
     * @ORM\Column(name="scheduleId", type="string")
     */
    private $scheduleId;

    /**
     * @var integer
     * @ORM\Column(name="dayNumber", type="integer")
     */
    private $dayNumber;

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

    public static function getDayOfWeek(int $dayNumber)
    {
        switch ($dayNumber) {
            case 0:
                return 'Sunday';
            case 1:
                return 'Monday';
            case 2:
                return 'Tuesday';
            case 3:
                return 'Wednesday';
            case 4:
                return 'Thursday';
            case 5:
                return 'Friday';
            case 6:
                return 'Saturday';
        }

        return 'invalid';
    }


    public static function getNextDay(int $dayNumber)
    {
        if ($dayNumber === 6) {
            return 0;
        }

        return $dayNumber + 1;
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