<?php
/**
 * Created by patrickclover on 11/12/2017 at 22:16
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Device;

use App\Models\User\UserAgent;
use Doctrine\ORM\Mapping as ORM;

/**
 * DeviceBrowser
 *
 * @ORM\Table(name="device_browser")
 * @ORM\Entity
 */
class DeviceBrowser
{

    public function __construct($browser)
    {
        $this->type          = $browser['type'];
        $this->name          = $browser['name'];
        $this->shortName     = $browser['short_name'];
        $this->version       = $browser['version'];
        $this->engine        = $browser['engine'];
        $this->engineVersion = $browser['engine_version'];
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
     * @ORM\Column(name="type", type="string")
     */
    private $type;

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
     * @ORM\Column(name="engine", type="string")
     */
    private $engine;

    /**
     * @var string
     * @ORM\Column(name="engine_version", type="string")
     */
    private $engineVersion;


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