<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 10:10
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit Note Line Item
 *
 * @ORM\Table(name="customer_credit_note_line_item")
 * @ORM\Entity
 */
class CreditNoteLineItem
{
    /**
     * @var string
     * @ORM\Column(name="id", length=50, type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="creditNoteId", type="string")
     */
    private $credit_note_id;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", type="string")
     */
    private $subscription_id;

    /**
     * @var integer
     * @ORM\Column(name="dateFrom", type="integer")
     */
    private $date_from;

    /**
     * @var integer
     * @ORM\Column(name="dateTo", type="integer")
     */
    private $date_to;

    /**
     * @var integer
     * @ORM\Column(name="unitAmount", type="integer")
     */
    private $unit_amount;

    /**
     * @var integer
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var boolean
     * @ORM\Column(name="isTaxed", type="boolean")
     */
    private $is_taxed;

    /**
     * @var integer
     * @ORM\Column(name="taxAmount", type="integer")
     */
    private $tax_amount;

    /**
     * @var integer
     * @ORM\Column(name="taxRate", type="integer")
     */
    private $tax_rate;

    /**
     * @var integer
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @var integer
     * @ORM\Column(name="discountAmount", type="integer")
     */
    private $discountAmount;

    /**
     * @var integer
     * @ORM\Column(name="itemLevelDiscountAmount", type="integer")
     */
    private $item_level_discount_amount;

    /**
     * @var string
     * @ORM\Column(name="description", type="string")
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="entityType", type="string")
     */
    private $entity_type;

    /**
     * @var string
     * @ORM\Column(name="taxExemptReason", type="string")
     */
    private $tax_exempt_reason;

    /**
     * @var string
     * @ORM\Column(name="entityId", type="string")
     */
    private $entity_id;

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