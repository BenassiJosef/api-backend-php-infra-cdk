<?php
/**
 * Created by jamieaitken on 28/05/2018 at 16:10
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationPolicyGroupSerials
 *
 * @ORM\Table(name="location_policy_group_serials")
 * @ORM\Entity
 */
class LocationPolicyGroupSerials
{

    public function __construct(string $groupId, string $serial)
    {
        $this->groupId = $groupId;
        $this->serial  = $serial;
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
     * @ORM\Column(name="groupId", type="string")
     */
    private $groupId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

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