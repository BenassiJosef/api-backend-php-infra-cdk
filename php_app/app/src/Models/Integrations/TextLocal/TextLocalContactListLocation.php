<?php
/**
 * Created by jamieaitken on 26/09/2018 at 15:20
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\TextLocal;

use Doctrine\ORM\Mapping as ORM;

/**
 * TextLocalContactListLocation
 *
 * @ORM\Table(name="text_local_contact_list_location")
 * @ORM\Entity
 */
class TextLocalContactListLocation
{

    public function __construct(
        string $serial,
        string $detailsId,
        string $contactListId,
        string $contactListName,
        string $event
    )
    {
        $this->serial          = $serial;
        $this->detailsId       = $detailsId;
        $this->contactListId   = $contactListId;
        $this->contactListName = $contactListName;
        $this->onEvent         = $event;
        $this->enabled         = true;
        $this->deleted         = false;
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
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="detailsId", type="string")
     */

    private $detailsId;

    /**
     * @var string
     * @ORM\Column(name="contactListId", type="string")
     */
    private $contactListId;

    /**
     * @var string
     * @ORM\Column(name="contactListName", type="string")
     */
    private $contactListName;

    /**
     * @var bool
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var bool
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;


    /**
     * @var string
     * @ORM\Column(name="onEvent", type="string")
     */
    private $onEvent;

    /**
     * @var string
     * @ORM\Column(name="filterListId", type="string")
     */
    private $filterListId;

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