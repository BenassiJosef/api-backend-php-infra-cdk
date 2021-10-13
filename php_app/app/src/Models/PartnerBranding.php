<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerBranding
 *
 * @ORM\Table(name="partner_branding")
 * @ORM\Entity
 */
class PartnerBranding
{

    public function __construct(string $admin, $branding)
    {
        $this->admin    = $admin;
        $this->branding = $branding;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="admin", type="string")
     */
    private $admin;

    /**
     * @var string
     *
     * @ORM\Column(name="branding", type="json_array", length=65535, nullable=true)
     */
    private $branding;

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

