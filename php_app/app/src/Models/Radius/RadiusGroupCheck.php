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
 * @ORM\Table(name="radgroupcheck")
 * @ORM\Entity
 */
class RadiusGroupCheck
{

    public function __construct($groupname, $value, $attribute)
    {
        $this->op        = '==';
        $this->groupname = $groupname;
        $this->value     = $value;
        $this->attribute = $attribute;
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
     * @ORM\Column(name="groupname", type="string")
     */
    private $groupname;

    /**
     * @var string
     * @ORM\Column(name="value", type="string")
     */
    private $value;

    /**
     * @var string
     * @ORM\Column(name="op", type="string")
     */
    private $op;

    /**
     * @var string
     * @ORM\Column(name="attribute", type="string")
     */
    private $attribute;

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