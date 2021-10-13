<?php
/**
 * Created by jamieaitken on 30/10/2018 at 14:02
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImpressionsAggregate
 *
 * @ORM\Table(name="nearly_impressions_aggregate")
 * @ORM\Entity
 */
class ImpressionsAggregate
{

    public function __construct(
        string $serial,
        string $year,
        string $month,
        string $week,
        string $day,
        string $hour,
        \DateTime $formattedTimestamp
    ) {
        $this->serial             = $serial;
        $this->year               = $year;
        $this->month              = $month;
        $this->week               = $week;
        $this->day                = $day;
        $this->hour               = $hour;
        $this->formattedTimestamp = $formattedTimestamp;
        $this->impressions        = 0;
        $this->converted          = 0;
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", length=36)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="year", type="string")
     */
    private $year;

    /**
     * @var string
     * @ORM\Column(name="month", type="string")
     */
    private $month;

    /**
     * @var string
     * @ORM\Column(name="week", type="string")
     */
    private $week;

    /**
     * @var string
     * @ORM\Column(name="day", type="string")
     */
    private $day;

    /**
     * @var string
     * @ORM\Column(name="hour", type="string")
     */
    private $hour;

    /**
     * @var \DateTime
     * @ORM\Column(name="formattedTimestamp", type="datetime")
     */
    private $formattedTimestamp;

    /**
     * @var integer
     * @ORM\Column(name="impressions", type="integer")
     */
    private $impressions;

    /**
     * @var integer
     * @ORM\Column(name="converted", type="integer")
     */
    private $converted;

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