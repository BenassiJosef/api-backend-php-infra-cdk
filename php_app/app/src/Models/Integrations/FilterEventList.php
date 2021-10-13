<?php
/**
 * Created by jamieaitken on 03/10/2018 at 10:21
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * FilterEventList
 *
 * @ORM\Table(name="integration_event_lists")
 * @ORM\Entity
 */
class FilterEventList
{

    public function __construct(?Organization $organization, string $name, string $type)
    {
        $this->uid            = is_null($organization) ? null : $organization->getOwnerId()->toString();
        $this->organizationId = is_null($organization) ? null : $organization->getId();
        $this->name           = $name;
        $this->type           = $type;
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
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

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