<?php
/**
 * Created by jamieaitken on 28/05/2018 at 15:48
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * LocationPolicyGroup
 *
 * @ORM\Table(name="location_policy_group")
 * @ORM\Entity
 */
class LocationPolicyGroup
{

    public function __construct(Organization $organization, string $name)
    {
        $this->name           = $name;
        $this->createdBy      = $organization->getOwnerId()->toString();
        $this->organizationId = $organization->getId();
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
     * @ORM\Column(name="createdBy", type="string")
     */
    private $createdBy;

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