<?php
/**
 * Created by jamieaitken on 24/11/2017 at 11:50
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\UniFi;

use App\Models\Locations\Informs\Inform;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UnifiLocation
 *
 * @ORM\Table(name="unifi_location")
 * @ORM\Entity
 */
class UnifiLocation implements JsonSerializable
{

    public function __construct(string $serial)
    {
        $this->serial = $serial;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="serial", type="string")
     *
     */
    private $serial;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Integrations\UniFi\UnifiController", cascade={"persist"})
     * @ORM\JoinColumn(name="unifiControllerId", referencedColumnName="id", nullable=false)
     * @var UnifiController $controller
     */
    private $controller;

    /**
     * @var Inform
     */
    private $inform;

    /**
     * @var string
     * @ORM\Column(name="unifiControllerId", type="string")
     */
    private $unifiControllerId;

    /**
     * @var string
     * @ORM\Column(name="unifiId", type="string")
     *
     */
    private $unifiId;

    /**
     * @var integer
     * @ORM\Column(name="timeout", type="integer")
     *
     */
    private $timeout;

    /**
     * @var boolean
     * @ORM\Column(name="status", type="boolean")
     *
     */
    private $status;

    /**
     * @var boolean
     * @ORM\Column(name="multi_site", type="boolean")
     *
     */
    private $multiSite;

    /**
     * @var string
     * @ORM\Column(name="multi_site_ssid", type="string")
     *
     */
    private $multiSiteSsid;

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

    public function setInform(Inform $inform)
    {
        $this->inform = $inform;
    }

    public function authUrl()
    {
        if (is_null($this->controller)) {
            return '';
        }
        $path = '/guest/s/' . $this->unifiId . '/login';
        $urlParse = parse_url($this->controller->getHostname());
        $hostname = $urlParse['host'];
        $port = 8880;
        $protocal = 'http://';
        if ($this->controller->getUseHttps()) {
            $port = 8443;
            $protocal = 'https://';
        }
        return $protocal . $hostname . ':' . $port . $path;
    }

    public function getSerial()
    {
        return $this->serial;
    }

    public function getMultiSiteSsid()
    {
        return $this->multiSiteSsid;
    }

    public function jsonSerialize()
    {
        return [
            'serial' => $this->serial,
            'unifi_id' => $this->unifiId,
            'multi_site_ssid' => $this->multiSiteSsid,
            'multisite' => $this->multiSite,
            'controller' => $this->controller,
            'auth_url' => $this->authUrl(),
            'controller_id' => $this->unifiControllerId,
            'inform' => $this->inform,
        ];
    }

}
