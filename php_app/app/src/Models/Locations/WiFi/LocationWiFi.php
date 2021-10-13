<?php

/**
 * Created by jamieaitken on 06/02/2018 at 13:32
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\WiFi;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * LocationWiFi
 *
 * @ORM\Table(name="network_settings_wifi")
 * @ORM\Entity
 */
class LocationWiFi implements JsonSerializable
{

    public function __construct(bool $disabled, string $ssid)
    {
        $this->disabled  = $disabled;
        $this->ssid      = $ssid;
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
     * @var boolean
     * @ORM\Column(name="disabled", type="boolean")
     */
    private $disabled;

    /**
     * @var string
     * @ORM\Column(name="ssid", type="string")
     */
    private $ssid;

    /**
     * @var /DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    public static function defaultDisabled()
    {
        return true;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'disabled' => $this->disabled,
            'ssid' => $this->ssid
        ];
    }

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
