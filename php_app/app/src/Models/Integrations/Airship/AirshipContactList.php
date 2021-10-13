<?php
/**
 * Created by jamieaitken on 2019-07-04 at 12:43
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\Airship;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * AirshipContactList
 *
 * @ORM\Table(name="airship_contact_list")
 * @ORM\Entity
 */
class AirshipContactList
{

    public function __construct(Organization $organization, string $detailsId)
    {
        $this->details        = $detailsId;
        $this->uid            = $organization->getOwnerId()->toString();
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
     * @ORM\Column(name="details", type="string")
     */
    private $details;

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