<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 26/01/2017
 * Time: 09:59
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
/**
 * Invoice
 *
 * @ORM\Table(name="paypal_customers")
 * @ORM\Entity
 */

class PayPalCustomer
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     **/

    private $id;

    /**
     * @var string
     * @ORM\Column (name="user", type="string")
     **/

    private $user;

    /**
     * @var string
     * @ORM\Column (name="pass", type="string")
     **/

    private $pass;

    /**
     * @var string
     * @ORM\Column (name="sig", type="string")
     **/

    private $sig;

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