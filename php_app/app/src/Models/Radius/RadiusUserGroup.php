<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/08/2017
 * Time: 19:30
 */

namespace App\Models\Radius;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RadiusUserGroup
 * @package App\Models
 *
 * @ORM\Table(name="radusergroup")
 * @ORM\Entity
 */
class RadiusUserGroup
{

    public function __construct($username, $group)
    {
        $this->username = $username;
        $this->group    = $group;
        $this->priority = 1;
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
     * @ORM\Column(name="username", type="string")
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(name="groupname", type="string")
     */
    private $group;

    /**
     * @var integer
     * @ORM\Column(name="priority", type="integer")
     */
    private $priority;

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