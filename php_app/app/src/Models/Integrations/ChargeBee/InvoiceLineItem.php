<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/07/2017
 * Time: 14:32
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice Line Item
 *
 * @ORM\Table(name="customer_invoice_line_item")
 * @ORM\Entity
 */
class InvoiceLineItem
{
    /**
     * @var string
     * @ORM\Column(name="id", length=50, type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="invoiceId", length=50, nullable=false)
     */
    private $invoice_id;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", length=17)
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
     * @var float
     * @ORM\Column(name="taxRate", type="float")
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
    private $discount_amount;

    /**
     * @var integer
     * @ORM\Column(name="itemLevelDiscountAmount", type="integer")
     */
    private $item_level_discount_amount;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=250)
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
     * @var boolean
     * @ORM\Column(name="isDeleted", type="boolean")
     */
    private $is_deleted;

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