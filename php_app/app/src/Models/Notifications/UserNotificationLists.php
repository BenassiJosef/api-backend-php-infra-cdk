<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 07/09/2017
 * Time: 15:53
 */

namespace App\Models\Notifications;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="user_notification_lists")
 * @ORM\Entity
 */
class UserNotificationLists
{

    public function __construct(string $user)
    {
        $this->uid              = $user;
        $this->notificationList = Strings::idGenerator('nli');
        $this->hasSeen          = false;
        $this->updatedAt        = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     * @ORM\Id
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="notificationList", type="string")
     */
    private $notificationList;

    /**
     * @var boolean
     * @ORM\Column(name="hasSeen", type="boolean")
     */
    private $hasSeen;

    /**
     * @var /DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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
}