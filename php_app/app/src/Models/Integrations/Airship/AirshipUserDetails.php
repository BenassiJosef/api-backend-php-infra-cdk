<?php
/**
 * Created by jamieaitken on 2019-07-04 at 12:47
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\Airship;

use Doctrine\ORM\Mapping as ORM;

/**
 * AirshipUserDetails
 *
 * @ORM\Table(name="airship_user_details")
 * @ORM\Entity
 */
class AirshipUserDetails
{

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
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
     * @ORM\Column(name="apiKey", type="string")
     */
    private $apiKey;

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