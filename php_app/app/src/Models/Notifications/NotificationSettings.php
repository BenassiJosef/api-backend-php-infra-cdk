<?php
/**
 * Created by jamieaitken on 23/11/2017 at 11:32
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Notifications;

use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationSettings
 *
 * @ORM\Table(name="notification_settings")
 * @ORM\Entity
 */
class NotificationSettings
{

    public function __construct(string $userId, $notificationKind)
    {
        $this->uid              = $userId;
        $this->notificationKind = $notificationKind;
        $this->hidden           = false;
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
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="notificationKind", type="string")
     */
    private $notificationKind;

    /**
     * @var boolean
     * @ORM\Column(name="hidden", type="boolean")
     */
    private $hidden;

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