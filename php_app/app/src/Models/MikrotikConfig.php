<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 21/02/2017
 * Time: 08:18
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkAccess
 *
 * @ORM\Table(name="mikrotik_config")
 * @ORM\Entity
 */
class MikrotikConfig
{

    /**
     * MikrotikConfig constructor.
     * @param $file
     * @param $serial
     */

    function __construct($file, $serial)
    {
        $this->file      = $file;
        $this->serial    = $serial;
        $this->createdAt = new \DateTime();
        $this->deleted   = false;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
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
     * @ORM\Column(name="file", type="string", length=200)
     */
    private $file = '';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted;

    /**
     * Get array copy of object
     *
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