<?php

/**
 * Created by jamieaitken on 06/03/2019 at 13:23
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly\Stories;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * NearlyStory
 *
 * @ORM\Table(name="nearly_story")
 * @ORM\Entity
 */
class NearlyStory implements JsonSerializable
{

    public function __construct(string $serial, int $interval)
    {
        $this->createdAt     = new \DateTime();
        $this->updatedAt     = new \DateTime();
        $this->serial        = $serial;
        $this->storyInterval = $interval;
        $this->enabled       = true;
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
     * @var boolean
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var integer
     * @ORM\Column(name="storyInterval", type="integer")
     */
    private $storyInterval;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var NearlyStoryPage[]
     */
    private $pages = [];


    public function getId(): string
    {
        return $this->id;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled)
    {
        $this->enabled =  $enabled;
    }

    /**
     * @param NearlyStoryPage[] $pages
     */
    public function setPages(array $pages)
    {
        $this->pages =  $pages;
    }

    /**
     * @return NearlyStoryPage[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    public function jsonSerialize()
    {
        return [
            'pages'    => $this->getPages(),
            'serial'    => $this->serial,
            'enabled'     => $this->enabled,
            'story_interval' => $this->storyInterval

        ];
    }

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
