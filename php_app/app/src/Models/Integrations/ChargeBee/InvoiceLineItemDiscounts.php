<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 07/07/2017
 * Time: 10:22
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice Line Item Discount
 *
 * @ORM\Table(name="customer_invoice_line_item_discount")
 * @ORM\Entity
 */
class InvoiceLineItemDiscounts
{

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="lineItemId", type="string", length=50, nullable=false)
     */
    private $line_item_id;

    /**
     * @var string
     * @ORM\Column(name="invoiceId", length=50, nullable=false)
     */
    private $invoice_id;

    /**
     * @var string
     * @ORM\Column(name="discountType", type="string", length=42, nullable=false)
     */
    private $discount_type;

    /**
     * @var string
     * @ORM\Column(name="couponId", type="string", length=50, nullable=false)
     */
    private $coupon_id;

    /**
     * @var integer
     * @ORM\Column(name="discountAmount", type="integer", nullable=false)
     */
    private $discount_amount;

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