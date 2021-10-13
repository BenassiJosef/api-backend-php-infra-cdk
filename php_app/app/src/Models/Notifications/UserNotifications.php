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
 * @ORM\Table(name="user_notifications")
 * @ORM\Entity
 */
class UserNotifications
{
    public function __construct(string $notificationList, string $notificationId)
    {
        $this->userNotificationListId = $notificationList;
        $this->notificationId         = $notificationId;
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
     * @ORM\Column(name="notificationListId", type="string")
     */
    private $userNotificationListId;

    /**
     * @var string
     * @ORM\Column(name="notificationId", type="string")
     */
    private $notificationId;

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