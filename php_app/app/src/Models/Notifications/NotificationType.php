<?php
/**
 * Created by jamieaitken on 27/11/2017 at 10:17
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Notifications;

use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationType
 *
 * @ORM\Table(name="notification_extra_type")
 * @ORM\Entity
 */
class NotificationType
{

    private $hiddenNotifications = [
        'billing_error',
        'billing_invoice_ready',
        'card_expiry_reminder'
    ];

    public function __construct(string $uid, string $type, string $notificationKind)
    {
        $this->uid              = $uid;
        $this->type             = $type;
        $this->notificationKind = $notificationKind;
        if (in_array($notificationKind, $this->hiddenNotifications)) {
            $this->hidden = true;
        } else {
            $this->hidden = false;
        }
    }

    /**
     * @var integer
     *
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
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="notificationKind", type="string")
     */
    private $notificationKind;

    /**
     * @var string
     * @ORM\Column(name="additionalInfo", type="string")
     */
    private $additionalInfo;

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