<?php
/**
 * Created by jamieaitken on 07/02/2018 at 10:55
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Timeout;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationTimeout
 *
 * @ORM\Table(name="network_settings_timeout")
 * @ORM\Entity
 */
class LocationTimeout
{

    public function __construct(string $idle, string $session, string $locationOtherId, string $kind)
    {
        $this->locationOtherId = $locationOtherId;
        $this->kind            = $kind;
        $this->idle            = $idle;
        $this->session         = $session;
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
     * @var string
     * @ORM\Column(name="idleTimeout", type="string")
     */
    private $idle;

    /**
     * @var string
     * @ORM\Column(name="sessionTimeout", type="string")
     */
    private $session;

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

    public static function defaultFreeIdle()
    {
        return '59m';
    }

    public static function defaultFreeSession()
    {
        return '10h';
    }

    public static function defaultPaidIdle()
    {
        return '59m';
    }

    public static function defaultPaidSession()
    {
        return '10h';
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