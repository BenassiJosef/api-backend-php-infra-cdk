<?php
/**
 * Created by jamieaitken on 15/03/2018 at 16:28
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Members;

use Doctrine\ORM\Mapping as ORM;

/**
 * StandalonePartner
 *
 * @ORM\Table(name="standalone_partner")
 * @ORM\Entity
 */
class StandalonePartner
{

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="partnerBrandingId")
     */
    private $partnerBrandingId;

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