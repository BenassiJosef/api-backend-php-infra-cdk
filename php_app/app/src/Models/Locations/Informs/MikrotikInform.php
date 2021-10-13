<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 14/03/2017
 * Time: 13:48
 */

namespace App\Models\Locations\Informs;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MikrotikInform
 * @package App\Models\Locations\Informs
 *
 * @ORM\Table(name="inform_mikrotik")
 * @ORM\Entity
 */
class MikrotikInform
{
    public function __construct($informId, $cpu, $cpuWarning, $masterSite, $master, $model, $waitingConfig, $osVersion)
    {
        $this->informId      = $informId;
        $this->cpu           = $cpu;
        $this->cpuWarning    = $cpuWarning;
        $this->masterSite    = $masterSite;
        $this->master        = $master;
        $this->model         = $model;
        $this->waitingConfig = $waitingConfig;
        $this->osVersion     = $osVersion;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="inform_id", type="string")
     */
    private $informId;

    /**
     * @var integer
     * @ORM\Column(name="cpu_usage", type="integer")
     */
    private $cpu;

    /**
     * @var boolean
     * @ORM\Column(name="cpu_warning", type="boolean")
     */
    private $cpuWarning;

    /**
     * @var string
     * @ORM\Column(name="master_site", type="string")
     */
    private $masterSite;

    /**
     * @var boolean
     * @ORM\Column(name="master", type="boolean")
     */
    private $master;

    /**
     * @var string
     * @ORM\Column(name="model", type="string")
     */
    private $model;

    /**
     * @var boolean
     * @ORM\Column(name="waiting_config", type="boolean")
     */
    private $waitingConfig;

    /**
     * @var string
     * @ORM\Column(name="os_version", type="string")
     */
    private $osVersion;

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
