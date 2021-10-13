<?php
/**
 * Created by jamieaitken on 07/02/2018 at 10:15
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Bandwidth;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationBandwidth
 *
 * @ORM\Table(name="network_settings_bandwidth")
 * @ORM\Entity
 */
class LocationBandwidth
{

    public function __construct(int $download, int $upload, string $locationId, string $kind)
    {
        $this->download        = $download;
        $this->upload          = $upload;
        $this->locationOtherId = $locationId;
        $this->kind            = $kind;
        $this->updatedAt       = new \DateTime();
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
     * @ORM\Column(name="locationOtherId", type="string")
     */
    private $locationOtherId;

    /**
     * @var integer
     * @ORM\Column(name="download", type="integer")
     */
    private $download;

    /**
     * @var integer
     * @ORM\Column(name="upload", type="integer")
     */
    private $upload;

    /**
     * @var string
     * @ORM\Column(name="kind", type="string")
     */
    private $kind;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;


    public static function defaultFreeDownload()
    {
        return 2048;
    }

    public static function defaultFreeUpload()
    {
        return 512;
    }

    public static function defaultPaidDownload()
    {
        return 2048;
    }

    public static function defaultPaidUpload()
    {
        return 512;
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