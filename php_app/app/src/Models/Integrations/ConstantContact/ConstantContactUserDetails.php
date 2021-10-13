<?php
/**
 * Created by jamieaitken on 09/10/2018 at 10:55
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\ConstantContact;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConstantContactUserDetails
 *
 * @ORM\Table(name="constant_contact_user_details")
 * @ORM\Entity
 */
class ConstantContactUserDetails
{

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
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
     * @ORM\Column(name="accessToken", type="string")
     */
    private $accessToken;

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