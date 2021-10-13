<?php
/**
 * Created by jamieaitken on 30/07/2018 at 16:52
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Reviews;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationReviews
 *
 * @ORM\Table(name="location_reviews")
 * @ORM\Entity
 */
class LocationReviews
{

    public function __construct(string $serial, string $reviewType)
    {
        $this->serial     = $serial;
        $this->reviewType = $reviewType;
        $this->enabled    = true;
        $this->hasBias    = false;
        $this->bias       = 0;
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
     * @ORM\Column(name="reviewType", type="string")
     */

    private $reviewType;

    /**
     * @var boolean
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var boolean
     * @ORM\Column(name="hasBias", type="boolean")
     */
    private $hasBias;

    /**
     * @var float
     * @ORM\Column(name="bias", type="float")
     */
    private $bias;

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