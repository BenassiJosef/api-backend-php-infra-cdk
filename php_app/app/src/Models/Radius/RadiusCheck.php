<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 14/02/2017
 * Time: 15:24
 */

namespace App\Models\Radius;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RadiusCheck
 * @package App\Models
 *
 * @ORM\Table(name="radcheck")
 * @ORM\Entity
 */
class RadiusCheck
{

    public function __construct($username, $password)
    {
        $this->username  = $username;
        $this->password  = $password;
        $this->attribute = 'Cleartext-Password';
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
     * @ORM\Column(name="attribute", type="string")
     */
    private $attribute;

    /**
     * @var string
     * @ORM\Column(name="value", type="string")
     */
    private $password;

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