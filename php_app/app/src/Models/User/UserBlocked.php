<?php
/**
 * Created by jamieaitken on 08/11/2018 at 11:35
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserBlocked
 *
 * @ORM\Table(name="user_blocked")
 * @ORM\Entity
 */
class UserBlocked
{

    public function __construct(string $serial, string $mac)
    {
        $this->mac       = $mac;
        $this->serial    = $serial;
        $this->createdAt = new \DateTime();
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
     * @ORM\Column(name="mac", type="string")
     */
    private $mac;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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