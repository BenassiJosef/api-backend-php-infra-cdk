<?php
/**
 * Created by jamieaitken on 28/09/2018 at 14:31
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\MailChimp;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * MailChimpContactList
 *
 * @ORM\Table(name="mail_chimp_contact_list")
 * @ORM\Entity
 */
class MailChimpContactList
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