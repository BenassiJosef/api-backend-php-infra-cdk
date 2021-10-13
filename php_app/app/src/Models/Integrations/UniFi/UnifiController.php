<?php

namespace App\Models\Integrations\UniFi;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UnifiController
 *
 * @ORM\Table(name="unifi_controller")
 * @ORM\Entity
 */
class UnifiController implements JsonSerializable
{

    public function __construct(string $hostname, string $username, string $password, string $version)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->version = $version;
        $this->lastRequest = new \DateTime();
        $this->id = Strings::idGenerator('uniCtrl');
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="hostname", type="string", length=100, nullable=true)
     */
    private $hostname;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=100, nullable=true)
     */
    private $password;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastRequest", type="datetime")
     */
    private $lastRequest;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=10, nullable=true)
     */
    private $version;

    /**
     * @var bool
     *
     * @ORM\Column(name="use_https", type="boolean", length=10, nullable=true)
     */
    private $useHttps;

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

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getUseHttps(): bool
    {
        return $this->useHttps ?? false;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'hostname' => $this->hostname,
            'username' => $this->username,
            'version' => $this->version,
            'use_https' => $this->useHttps,
        ];
    }

}
