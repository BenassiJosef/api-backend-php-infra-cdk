<?php

namespace App\Models\Members\Groups;

use Doctrine\ORM\Mapping as ORM;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 03/04/2017
 * Time: 17:07
 */
class GroupMembers
{

    public function __construct(string $groupId, string $user, int $role)
    {
        $this->groupId     = $groupId;
        $this->user        = $user;
        $this->roleInGroup = $role;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="groupId", type="string")
     */
    private $groupId;

    /**
     * @var string
     * @ORM\Column(name="user", type="string")
     */
    private $user;

    /**
     * @var integer
     * @ORM\Column(name="role", type="integer")
     */
    private $roleInGroup;

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
