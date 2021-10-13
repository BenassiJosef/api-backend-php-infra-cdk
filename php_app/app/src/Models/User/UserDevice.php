<?php

/**
 * Created by patrickclover on 11/12/2017 at 22:18
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\User;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UserDevice
 *
 * @ORM\Table(name="user_device")
 * @ORM\Entity
 */
class UserDevice implements JsonSerializable
{

    public function __construct($device)
    {
        $this->mac       = $device['mac'];
        $this->mobile    = $device['mobile'];
        $this->type      = $device['type'];
        $this->brand     = $device['brand'];
        $this->model     = $device['model'];
        $this->shortName = $device['short_name'];
        $this->agents    = new ArrayCollection();
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
     * @ORM\Column(name="mac", type="string")
     */
    private $mac;

    /**
     * @var integer
     * @ORM\Column(name="mobile", type="integer")
     */
    private $mobile;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="brand", type="string")
     */
    private $brand;

    /**
     * @var string
     * @ORM\Column(name="model", type="string")
     */
    private $model;

    /**
     * @var string
     * @ORM\Column(name="short_name", type="string")
     */
    private $shortName;

    /* WHEN WE CAN TIE BACK TO USER PROFILE
    /**
     * @ORM\ManyToOne(targetEntity="UserProfile", inversedBy="devices", cascade={"all"})
     * @ORM\JoinColumn(name="profileId", referencedColumnName="id")

    private $profile;
    */

    /**
     * @ORM\OneToMany(targetEntity="UserAgent", mappedBy="device")
     * @ORM\JoinColumn(name="id", referencedColumnName="user_device_id")
     */
    private $agents;

    public function getAgents()
    {
        return $this->agents;
    }

    public function addAgent(UserAgent $agent)
    {
        if ($this->agents->contains($agent)) {
            $this->agents->add($agent);
        }

        return $this;
    }

    public function removeAgent(UserAgent $agent)
    {
        if ($this->agents->contains($agent)) {
            $this->agents->removeElement($agent);
        }

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'mac'    => $this->mac,
            'brand'    => $this->brand,
            'model'     => $this->model
        ];
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
