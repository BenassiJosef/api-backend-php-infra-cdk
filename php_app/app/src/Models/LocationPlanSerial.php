<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/01/2017
 * Time: 16:11
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LocationPlanSerial
 * @package App\Models
 *
 * @ORM\Table(name="location_plans_serials")
 * @ORM\Entity
 */
class LocationPlanSerial
{

    public function __construct($planId, $serial)
    {
        $this->planId = $planId;
        $this->serial = $serial;
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
     * @ORM\Column(name="planId", type="string")
     */
    private $planId;


    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */

    private $serial;

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