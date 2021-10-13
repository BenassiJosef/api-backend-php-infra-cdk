<?php
/**
 * Created by jamieaitken on 20/11/2017 at 16:16
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\IPInfo;

use Doctrine\ORM\Mapping as ORM;

/**
 * IPInfo
 *
 * @ORM\Table(name="ipInfo_ips")
 * @ORM\Entity
 */
class IPInfo
{

    public function __construct(string $ip, $latitude, $longitude, $countryCode)
    {
        $this->ip          = $ip;
        $this->latitude    = $latitude;
        $this->longitude   = $longitude;
        $this->countryCode = $countryCode;
    }

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="ip", type="string")
     */
    private $ip;

    /**
     * @var float
     * @ORM\Column(name="latitude", type="float")
     */
    private $latitude;

    /**
     * @var float
     * @ORM\Column(name="longitude", type="float")
     */

    private $longitude;

    /**
     * @var string
     * @ORM\Column(name="countryCode", type="string")
     */
    private $countryCode;

    /**
     * Get array copy of object
     *
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