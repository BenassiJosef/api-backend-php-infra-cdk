<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Calendar
 *
 * @ORM\Table(name="hour_calendar")
 * @ORM\Entity
 */
class HourCalendar
{
    /**
     * @var \DateTime
     * @ORM\Id
     * @ORM\Column(name="timestamp", type="datetime")
     */
    private $date = '0000-00-00';


}

