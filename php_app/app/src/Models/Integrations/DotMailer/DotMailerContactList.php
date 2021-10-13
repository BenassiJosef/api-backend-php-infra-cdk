<?php
/**
 * Created by jamieaitken on 01/10/2018 at 14:32
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\DotMailer;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * DotMailerContactList
 *
 * @ORM\Table(name="dot_mailer_contact_list")
 * @ORM\Entity
 */
class DotMailerContactList
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