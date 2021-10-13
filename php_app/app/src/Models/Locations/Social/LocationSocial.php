<?php

/**
 * Created by jamieaitken on 07/02/2018 at 16:24
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Social;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * LocationSocial
 *
 * @ORM\Table(name="network_settings_social")
 * @ORM\Entity
 */
class LocationSocial implements JsonSerializable
{

    public function __construct(bool $enabled, string $kind, string $page)
    {
        $this->kind    = $kind;
        $this->page    = $page;
        $this->enabled = $enabled;
        $this->updatedAt = new \DateTime();
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
     * @ORM\Column(name="kind", type="string")
     */
    private $kind;

    /**
     * @var string
     * @ORM\Column(name="page", type="string")
     */
    private $page;

    /**
     * @var boolean
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "kind" => $this->kind,
            "page" => $this->page,
            "enabled" => $this->enabled
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
