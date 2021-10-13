<?php

namespace App\Models\Locations;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * User Opt Out
 *
 * @ORM\Table(name="user_opt_out")
 * @ORM\Entity
 */
class LocationOptOut
{
    public function __construct(string $profileId, string $serial)
    {
        $this->id        = Strings::idGenerator('optOut');
        $this->profileId = $profileId;
        $this->serial    = $serial;
        $this->deleted   = 0;
        $this->updatedAt = new \DateTime();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="profileId", type="string", nullable=false)
     */
    private $profileId;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", nullable=false)
     */
    private $serial;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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