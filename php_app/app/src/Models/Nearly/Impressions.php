<?php

/**
 * Created by jamieaitken on 30/10/2018 at 14:00
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly;

use Doctrine\ORM\Mapping as ORM;

/**
 * Impressions
 *
 * @ORM\Table(name="nearly_impressions")
 * @ORM\Entity
 */
class Impressions
{

    public function __construct(?string $profileId, string $serial)
    {
        $this->converted         = false;
        $this->impression        = true;
        $this->profileId         = $profileId;
        $this->serial            = $serial;
        $this->impressionCreated = new \DateTime();
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
     * @ORM\Column(name="profileId", type="string")
     */
    private $profileId;

    /**
     * @var boolean
     * @ORM\Column(name="converted", type="boolean")
     */
    private $converted;

    /**
     * @var boolean
     * @ORM\Column(name="impression", type="boolean")
     */
    private $impression;

    /**
     * @var \DateTime
     * @ORM\Column(name="impressionCreated", type="datetime")
     */
    private $impressionCreated;

    /**
     * @var \DateTime
     * @ORM\Column(name="conversionCreated", type="datetime")
     */
    private $conversionCreated;

    public function getSerial(): string
    {
        return $this->serial;
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
