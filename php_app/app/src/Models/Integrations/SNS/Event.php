<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/05/2017
 * Time: 13:23
 */

namespace App\Models\Integrations\SNS;

class Event
{
    public function __construct(string $topic, string $joiningId)
    {
        $this->topic     = $topic;
        $this->joiningId = $joiningId;
        $this->createdAt = new \DateTime();
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
     * @ORM\Column(name="type", type="string", length=256, nullable=false)
     */
    private $topic;

    /**
     * @var string
     * @ORM\Column(name="joiningId", type="string", length=256, nullable=false)
     */
    private $joiningId;

    /**
     * @var /DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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