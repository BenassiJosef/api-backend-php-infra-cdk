<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 10:30
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit Note Line Item Discounts
 *
 * @ORM\Table(name="customer_credit_note_line_item_discounts")
 * @ORM\Entity
 */
class CreditNoteLineItemDiscounts
{
    /**
     * @var string
     * @ORM\Column(name="lineItemId", type="string", length=50, nullable=false)
     * @ORM\Id
     */
    private $line_item_id;

    /**
     * @var string
     * @ORM\Column(name="creditNoteId", length=50, nullable=false)
     */
    private $credit_note_id;

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