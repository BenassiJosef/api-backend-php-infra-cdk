<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 25/01/2017
 * Time: 09:15
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class NetworkAccessMembers
 * @package App\Models
 *
 * @ORM\Table(name="network_access_members")
 * @ORM\Entity
 */
class NetworkAccessMembers
{

    public function __construct($memberId, $memberKey)
    {
        $this->memberId  = $memberId;
        $this->memberKey = $memberKey;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */

    private $id;

    /**
     * @var string
     * @ORM\Column(name="memberId", type="string", length=36)
     */

    private $memberId;

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