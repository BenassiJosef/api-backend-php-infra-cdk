<?php

namespace App\Models;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkAccess
 *
 * @ORM\Table(name="network_access", indexes={
 *     @ORM\Index(name="admin", columns={"admin"}),
 *     @ORM\Index(name="serial", columns={"serial"})
 * })
 * @ORM\Entity
 */
class NetworkAccess
{
    public function __construct(string $serial)
    {
        $this->id        = Strings::idGenerator('na');
        $this->memberKey = Strings::idGenerator('mk');
        $this->serial    = $serial;
    }

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     */
    private $id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial = '';

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=36, nullable=true)
     */
    private $admin;

    /**
     * @var string
     *
     * @ORM\Column(name="lastRegisteredAdmin", type="string", length=36)
     */
    private $lastRegisteredAdmin;

    /**
     * @ORM\Column(name="reseller", type="string", length=36, nullable=true)
     * @var string
     */
    private $reseller;

    /**
     * @var string
     * @ORM\Column(name="memberKey", type="string", length=36)
     */

    private $memberKey;


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

