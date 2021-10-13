<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UserData
 *
 * @ORM\Table(name="user_data", indexes={
 *     @ORM\Index(name="mac", columns={"mac"}),
 *     @ORM\Index(name="serial", columns={"serial"}),
 *     @ORM\Index(name="user_data_email", columns={"email"}),
 *     @ORM\Index(name="profileId", columns={"profileId"}),
 *     @ORM\Index(name="timestamp", columns={"timestamp"})
 * })
 * @ORM\Entity
 */
class UserData implements JsonSerializable
{
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
     *
     * @ORM\Column(name="mac", type="string", nullable=true)
     */
    private $mac;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=17, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="data_up", type="integer")
     */
    private $dataUp = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="data_down", type="integer")
     */
    private $dataDown = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    private $timestamp;

    /**
     * @var boolean
     *
     * @ORM\Column(name="auth", type="boolean", nullable=true)
     */
    private $auth;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastupdate", type="datetime", nullable=false)
     */
    private $lastupdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="auth_time", type="integer", nullable=false)
     */
    private $authTime = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=true)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="profileId", type="integer", nullable=true)
     */
    private $profileId;

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

    public function getUpTime()
    {
        if (is_null($this->timestamp) || is_null($this->lastupdate)) {
            return 0;
        }

        return  $this->lastupdate->getTimestamp() - $this->timestamp->getTimestamp();
    }

    public function jsonZapierSerialize()
    {
        return [
            'id'    => $this->id,
            'profile_id' => $this->profileId,
            'type' => $this->type,
            'ip' => $this->ip,
            'mac' => $this->mac,
            'timestamp' => $this->timestamp,
            'lastupdate' => $this->lastupdate,
            'auth_time' => $this->authTime,
            'uptime' => $this->getUpTime(),
            'totalUpload' => $this->dataUp,
            'totalDownload' => $this->dataDown
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function jsonSerialize()
    {
        return [
            'id'    => $this->id,
            'profile_id' => $this->profileId,
            'auth_time' => $this->authTime,
            'type' => $this->type,
            'ip' => $this->ip,
            'mac' => $this->mac,
            'uptime' => $this->getUpTime(),
            'timestamp' => $this->timestamp,
            'lastupdate' => $this->lastupdate
        ];
    }
}
