<?php
/**
 * Created by jamieaitken on 06/12/2017 at 13:40
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use App\Models\Organization;
use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * CustomerTemplate
 *
 * @ORM\Table(name="oauth_user_templates")
 * @ORM\Entity
 */
class CustomerTemplate
{

    public function __construct(Organization $organization, string $templateId)
    {
        $this->templateId = $templateId;
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
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="templateId", type="string")
     */
    private $templateId;

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