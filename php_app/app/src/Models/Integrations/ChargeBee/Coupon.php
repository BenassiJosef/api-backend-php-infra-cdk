<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/07/2017
 * Time: 10:09
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Coupons
 *
 * @ORM\Table(name="customer_coupon")
 * @ORM\Entity
 */
class Coupon
{
    /**
     * @var string
     * @ORM\Column(name="id", length=50, nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="name", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="invoiceName", length=100, nullable=false)
     */
    private $invoice_name;

    /**
     * @var string
     * @ORM\Column(name="discountType", length=12, nullable=false)
     */
    private $discount_type;

    /**
     * @var integer
     * @ORM\Column(name="discountAmount", type="integer")
     */
    private $discount_amount;

    /**
     * @var string
     * @ORM\Column(name="currencyCode", length=3, nullable=false)
     */
    private $currency_code;

    /**
     * @var float
     * @ORM\Column(name="discountPercentage", type="float")
     */
    private $discount_percentage;

    /**
     * @var string
     * @ORM\Column(name="applyOn", type="string", length=19)
     */
    private $apply_on;

    /**
     * @var string
     * @ORM\Column(name="applyDiscountOn", type="string")
     */
    private $apply_discount_on;

    /**
     * @var string
     * @ORM\Column(name="planConstraint", type="string")
     */
    private $plan_constraint;

    /**
     * @var string
     * @ORM\Column(name="addonConstraint", type="string")
     */
    private $addon_constraint;

    /**
     * @var string
     * @ORM\Column(name="planIds", type="json_array")
     */
    private $plan_ids;

    /**
     * @var string
     * @ORM\Column(name="addonIds", type="json_array")
     */
    private $addon_ids;

    /**
     * @var string
     * @ORM\Column(name="durationType", type="string", length=14)
     */
    private $duration_type;

    /**
     * @var integer
     * @ORM\Column(name="durationMonth", type="integer")
     */
    private $duration_month;

    /**
     * @var integer
     * @ORM\Column(name="validTill", type="integer")
     */
    private $valid_till;

    /**
     * @var integer
     * @ORM\Column(name="maxRedemptions", type="integer")
     */
    private $max_redemptions;

    /**
     * @var string
     * @ORM\Column(name="invoiceNotes", type="string", length=1000)
     */
    private $invoice_notes;

    /**
     * @var string
     * @ORM\Column(name="metadata", type="json_array", length=14)
     */
    private $meta_data;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=8)
     */
    private $status;

    /**
     * @var integer
     * @ORM\Column(name="createdAt", type="integer")
     */
    private $created_at;

    /**
     * @var integer
     * @ORM\Column(name="updatedAt", type="integer")
     */
    private $updated_at;

    /**
     * @var integer
     * @ORM\Column(name="resourceVersion", type="integer")
     */
    private $resource_version;

    /**
     * @var string
     * @ORM\Column(name="object", type="string")
     */
    private $object;

    /**
     * @var integer
     * @ORM\Column(name="redemptions", type="integer")
     */
    private $redemptions;

    /**
     * Get array copy of object
     *
     * @return array
     */
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