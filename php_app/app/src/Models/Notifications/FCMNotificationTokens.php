<?php

/**
 * Created by jamieaitken on 24/09/2018 at 13:36
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Notifications;

use Doctrine\ORM\Mapping as ORM;

/**
 * FCMNotifications
 *
 * @ORM\Table(name="fcm_notifications_tokens")
 * @ORM\Entity
 */
class FCMNotificationTokens
{

    public function __construct(string $uid, string $token, string $instanceId)
    {
        $this->uid = $uid;
        $this->token = $token;
        $this->instanceId = $instanceId;
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
     * @ORM\Column(name="uid", type="string", nullable=false)
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="token", type="string", nullable=false)
     */
    private $token;

    /**
     * @var string
     * @ORM\Column(name="instanceId", type="string")
     */
    private $instanceId;

    public function getToken()
    {
        return $this->token;
    }

    public function setToken(string $token)
    {
        $this->token = $token;
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
