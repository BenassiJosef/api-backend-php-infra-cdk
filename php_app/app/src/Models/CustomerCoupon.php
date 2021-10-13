<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 15/02/2017
 * Time: 09:26
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CustomerCoupon
 * @package App\Models
 *
 * @ORM\Table(name="customer_coupons")
 * @ORM\Entity
 */
class CustomerCoupon
{

    public function __construct($userId, $couponId)
    {
        $this->userId   = $userId;
        $this->couponId = $couponId;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(name="coupon", type="string")
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