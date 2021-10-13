<?php
/**
 * Created by jamieaitken on 28/09/2018 at 14:34
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\MailChimp;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailChimpUserDetails
 *
 * @ORM\Table(name="mail_chimp_user_details")
 * @ORM\Entity
 */
class MailChimpUserDetails
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