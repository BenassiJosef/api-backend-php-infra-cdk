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
 * Class SubscriptionVatRates
 * @package App\Models
 *
 * @ORM\Table(name="subscriptionVatRates")
 * @ORM\Entity
 */

class SubscriptionVatRates
{
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
     * @ORM\Column(name="name", length=20, nullable=false)
     */

    private $name;

    /**
     * @var string
     * @ORM\Column(name="code", length=2, nullable=false)
     */

    private $code;

    /**
     * @var integer
     * @ORM\Column(name="rate", length=3, type="integer")
     */

    private $rate = 20;

    /**
     * @var boolean
     * @ORM\Column(name="eu", length=2, type="boolean")
     */

    private $eu = true;

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