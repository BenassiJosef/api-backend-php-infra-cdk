<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 18/07/2017
 * Time: 14:34
 */

namespace App\Models\Integrations\ChargeBee;
use Doctrine\ORM\Mapping as ORM;

/**
 * Coupons
 *
 * @ORM\Table(name="customer_coupon_applied")
 * @ORM\Entity
 */
class CouponApplied
{
    /**
     * @var string
     * @ORM\Column(name="uid", length=36, nullable=false)
     * @ORM\Id
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="couponId", length=50, nullable=false)
     */
    private $couponId;

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