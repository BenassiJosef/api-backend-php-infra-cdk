<?php

namespace App\Models\Locations\Reports;

use Doctrine\ORM\Mapping as ORM;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 03/04/2017
 * Time: 12:22
 */

/**
 * OauthUser
 *
 * @ORM\Table(name="email_reports")
 * @ORM\Entity
 */
class EmailReport
{

    public function __construct(string $uid, string $serial, bool $daily, bool $weekly,
                                bool $biWeekly, bool $monthly, bool $biMonthly, int $timeStamp)
    {
        $this->uid           = $uid;
        $this->serial        = $serial;
        $this->daily         = $daily;
        $this->weekly        = $weekly;
        $this->biWeekly      = $biWeekly;
        $this->monthly       = $monthly;
        $this->biMonthly     = $biMonthly;
        $this->createdAt     = $timeStamp;
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
     * @ORM\Column(name="uid", type="string", length=36)
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string", length=12)
     */
    private $serial;

    /**
     * @var boolean
     * @ORM\Column(name="daily", type="boolean")
     */
    private $daily;

    /**
     * @var boolean
     * @ORM\Column(name="weekly", type="boolean")
     */
    private $weekly;

    /**
     * @var boolean
     * @ORM\Column(name="biWeekly", type="boolean")
     */
    private $biWeekly;

    /**
     * @var boolean
     * @ORM\Column(name="monthly", type="boolean")
     */
    private $monthly;

    /**
     * @var boolean
     * @ORM\Column(name="biMonthly", type="boolean")
     */
    private $biMonthly;

    /**
     * @var integer
     * @ORM\Column(name="createdAt", type="integer")
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

}