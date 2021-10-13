<?php
/**
 * Created by jamieaitken on 02/05/2018 at 17:41
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAccount
 *
 * @ORM\Table(name="user_profile_accounts")
 * @ORM\Entity
 */
class UserAccount
{

    public function __construct(string $profileId, string $password)
    {
        $this->id        = $profileId;
        $this->password  = $password;
        $this->updatedAt = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="password", type="string")
     */
    private $password;


    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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