<?php
/**
 * Created by jamieaitken on 19/06/2018 at 10:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Type;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationTypesSerial
 *
 * @ORM\Table(name="location_types_serial")
 * @ORM\Entity
 */
class LocationTypesSerial
{

    public function __construct(string $locationTypeId, string $serial)
    {
        $this->locationTypeId = $locationTypeId;
        $this->serial         = $serial;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="locationTypeId", type="string")
     */
    private $locationTypeId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

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