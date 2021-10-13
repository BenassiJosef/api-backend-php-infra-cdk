<?php
/**
 * Created by jamieaitken on 30/11/2017 at 16:21
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\UniFi;

use App\Models\Organization;
use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * UnifiControllerList
 *
 * @ORM\Table(name="unifi_controller_list")
 * @ORM\Entity
 */
class UnifiControllerList
{

    public function __construct(Organization $organization)
    {
        $this->uid = $organization->getOwnerId()->toString();
        $this->organizationId = $organization->getId();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="controllerId", type="string")
     */
    private $controllerId;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

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