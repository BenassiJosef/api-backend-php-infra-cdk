<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 04/02/2017
 * Time: 15:53
 */

namespace App\Models;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class CustomerVatSettings
 * @package App\Models
 *
 * @ORM\Table(name="customerVatSettings")
 * @ORM\Entity
 */

class CustomerVatSettings
{

    public function __construct($userId, $vatReference, $rate, $number)
    {
        $this->uid = $userId;
        $this->vatRef = $vatReference;
        $this->rate = $rate;
        $this->number = $number;
        $this->registered = true;
        $this->valid = true;
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
     *
     * @ORM\Column(name="uid", type="string", length=36, nullable=true)
     */

    private $uid;

    /**
     * @var string
     *
     * @ORM\Column(name="vatRef", type="string", length=36, nullable=true)
     */

    private $vatRef;

    /**
     * @var integer
     * @ORM\Column(name="rate", length=3, type="integer")
     */

    private $rate = 20;

    /**
     * @var integer
     * @ORM\Column(name="number", length=11, type="integer")
     */

    private $number = 0;


    /**
     * @var boolean
     * @ORM\Column(name="valid", length=1, type="boolean")
     */

    private $valid = true;

    /**
     * @var boolean
     * @ORM\Column(name="registered", length=1, type="boolean")
     */

    private $registered = true;

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