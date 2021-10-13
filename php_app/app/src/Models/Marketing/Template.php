<?php
/**
 * Created by jamieaitken on 06/12/2017 at 13:38
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * Template
 *
 * @ORM\Table(name="base_templates")
 * @ORM\Entity
 */
class Template
{

    public function __construct()
    {
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="content", type="string")
     */
    private $content;

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