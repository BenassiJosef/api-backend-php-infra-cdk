<?php
/**
 * Created by jamieaitken on 03/01/2018 at 17:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */
namespace App\Models\Integrations\UniFi;
use Doctrine\ORM\Mapping as ORM;

/**
 * UniFiLegacy
 *
 * @ORM\Table(name="unifi")
 * @ORM\Entity
 */
class UniFiLegacy
{

    public function __construct()
    {

    }

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="unifi_id", type="string")
     */
    private $unifiId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="hostname", type="string")
     */
    private $hostname;

    /**
     * @var string
     * @ORM\Column(name="username", type="string")
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(name="password", type="string")
     */
    private $password;

    /**
     * @var integer
     * @ORM\Column(name="timeout", type="integer")
     */
    private $timeout;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var \DateTime
     * @ORM\Column(name="lastRequest", type="datetime")
     */
    private $lastRequest;

    /**
     * @var string
     * @ORM\Column(name="version", type="string")
     */
    private $version;

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