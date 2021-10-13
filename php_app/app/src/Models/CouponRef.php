<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 14/02/2017
 * Time: 15:24
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Coupon
 * @package App\Models
 *
 * @ORM\Table(name="coupons")
 * @ORM\Entity
 */
class CouponRef
{

    public function __construct($name, $stripeId, $amountOff)
    {
        $this->name           = $name;
        $this->stripeCouponId = $stripeId;
        $this->amountOff      = $amountOff;
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
     * @ORM\Column(name="stripe_coupon_id", type="string")
     */
    private $stripeCouponId;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="amount", type="integer")
     */
    private $amountOff;

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