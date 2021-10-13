<?php
/**
 * Created by patrickclover on 11/12/2017 at 22:14
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Device;
use App\Models\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * DeviceOs
 *
 * @ORM\Table(name="device_os")
 * @ORM\Entity
 */
class DeviceOs
{

    public function __construct($os)
    {
        $this->name      = $os['name'];
        $this->shortName = $os['short_name'];
        $this->version   = $os['version'];
        $this->platform  = $os['platform'];
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
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="short_name", type="string")
     */
    private $shortName;

    /**
     * @var string
     * @ORM\Column(name="version", type="string")
     */
    private $version;

    /**
     * @var string
     * @ORM\Column(name="platform", type="string")
     */
    private $platform;

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