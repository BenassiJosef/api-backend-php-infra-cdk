<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 14/03/2017
 * Time: 13:47
 */

namespace App\Models\Locations\Informs;

use App\Models\Locations\Vendors;
use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Inform
 * @package App\Models\Locations\Informs
 *
 * @ORM\Table(name="inform")
 * @ORM\Entity
 */
class Inform implements JsonSerializable
{
    public function __construct($serial, $ip, $status, $vendor, Vendors $vendorSource)
    {
        $now             = new \DateTime();
        $this->serial    = $serial;
        $this->ip        = $ip;
        $this->timestamp = $now;
        $this->status    = $status;
        $this->vendor    = $vendor;
        $this->createdAt = $now;
        $this->vendorSource = $vendorSource;
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
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="ip", type="string")
     */
    private $ip;

    /**
     * @var \DateTime
     * @ORM\Column(name="timestamp", type="datetime")
     */
    private $timestamp;

    /**
     * @var \DateTime
     * @ORM\Column(name="onlineAt", type="datetime")
     */
    private $onlineAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="offlineAt", type="datetime")
     */
    private $offlineAt;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="vendor", type="string")
     */
    private $vendor;

    /**
     * @var string
     * @ORM\Column(name="radius_secret", type="string")
     */
    private $radiusSecret;

    /**
     * @var UuidInterface
     * @ORM\Column(name="vendor_source", type="string")
     */
    private $vendorSourceId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Locations\Vendors", cascade={"persist"})
     * @ORM\JoinColumn(name="vendor_source", referencedColumnName="id", nullable=false)
     * @var Vendors $vendorSource
     */
    private $vendorSource;


    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function jsonSerialize()
    {
        return [
            'serial'    => $this->serial,
            'ip'    => $this->ip,
            'last_seen_at'     => $this->timestamp,
            'status'   => $this->status,
            'vendor' => $this->vendor,
            'vendor_source' => $this->vendorSource->jsonSerialize(),
            'radius_secret' => $this->radiusSecret
        ];
    }

    public function getVendorSource(): Vendors
    {
        return $this->vendorSource;
    }

    public function setVendorSource(Vendors $vendor)
    {
        $this->vendor = $vendor->getName();
        $this->vendorSource = $vendor;
    }

    public function setRadiusSecret(string $radiusSecret)
    {
        $this->radiusSecret = $radiusSecret;
    }

    public function getRadiusSecret(): ?string
    {
        return $this->radiusSecret;
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
