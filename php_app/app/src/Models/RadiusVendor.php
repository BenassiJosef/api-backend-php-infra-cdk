<?php

namespace App\Models;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * RadiusVendor
 *
 * @ORM\Table(name="openmesh", indexes={
 *     @ORM\Index(name="serial", columns={"serial"})
 * })
 * @ORM\Entity
 */
class RadiusVendor
{
    public function __construct(string $serial, $secret, string $vendor)
    {
        $this->id        = Strings::idGenerator('rad');
        $this->serial    = $serial;
        $this->secret    = $secret;
        $this->vendor    = $vendor;
        $date            = new \DateTime();
        $this->createdAt = $date->getTimestamp();

    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     */

    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=18)
     */
    private $secret;

    /**
     * @var string
     *
     * @ORM\Column(name="vendor", type="string")
     */
    private $vendor;

    /**
     * @var integer
     *
     * @ORM\Column(name="createdAt", type="integer")
     */
    private $createdAt;

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

