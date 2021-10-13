<?php
/**
 * Created by patrickclover on 24/12/2017 at 23:28
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Auth;

use Doctrine\ORM\Mapping as ORM;

/**
 * Provider
 *
 * @ORM\Table(name="oauth_providers")
 * @ORM\Entity
 */
class Provider
{

    public function __construct($uid, $userId, $image, $type)
    {
        $this->uid    = $uid;
        $this->userId = $userId;
        $this->image  = $image;
        $this->type   = $type;
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
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="user_id", type="string")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="image", type="string")
     */
    private $image;


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