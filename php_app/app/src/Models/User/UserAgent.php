<?php
/**
 * Created by patrickclover on 11/12/2017 at 22:18
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\User;

use App\Models\Device\DeviceBrowser;
use App\Models\Device\DeviceOs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserAgent
 *
 * @ORM\Table(name="user_agent")
 * @ORM\Entity
 */
class UserAgent
{

    public function __construct()
    {
        $this->oss      = new ArrayCollection();
        $this->browsers = new ArrayCollection();
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
     * @ORM\Column(name="device_os_id", type="string")
     */
    private $deviceOsId;

    /**
     * @var string
     * @ORM\Column(name="device_browser_id", type="string")
     */
    private $deviceBrowserId;

    /**
     * @var string
     * @ORM\Column(name="user_device_id", type="string")
     */
    private $userDeviceId;

    /**
     * @ORM\ManyToOne(targetEntity="UserDevice", inversedBy="agents", cascade={"all"})
     * @ORM\JoinColumn(name="user_device_id", referencedColumnName="id")
     */
    private $device;


    public function getBrowsers()
    {
        return $this->browsers;
    }

    public function getOss()
    {
        return $this->oss;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function setDevice(UserDevice $device = null)
    {
        if ($this->device !== null) {
            $this->device->removeAgent($this);
        }

        if ($device !== null) {
            $device->addAgent($this);
        }

        $this->device = $device;

        return $this;
    }

    public function addOs(DeviceOs $os)
    {
        if ($this->oss->contains($os)) {
            $this->oss->add($os);
        }

        return $this;
    }

    public function removeOs(DeviceOs $os)
    {
        if ($this->oss->contains($os)) {
            $this->oss->removeElement($os);
        }

        return $this;
    }

    public function addBrowser(DeviceBrowser $browser)
    {
        if ($this->browsers->contains($browser)) {
            $this->browsers->add($browser);
        }

        return $this;
    }

    public function removeBrowser(DeviceBrowser $browser)
    {
        if ($this->browsers->contains($browser)) {
            $this->browsers->removeElement($browser);
        }

        return $this;
    }

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