<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 12/10/2017
 * Time: 16:19
 */

namespace App\Models\Marketing;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingOptOut
 *
 * @ORM\Table(name="marketing_user_opt")
 * @ORM\Entity
 */
class MarketingOptOut
{
    public function __construct(string $uid, string $serial, $type)
    {
        $this->id        = Strings::idGenerator('mopt');
        $this->uid       = $uid;
        $this->serial    = $serial;
        $this->type      = $type;
        $this->optOut    = true;
        $this->updatedAt = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var boolean
     * @ORM\Column(name="optOut", type="boolean", nullable=false)
     */
    private $optOut;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $updatedAt;

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