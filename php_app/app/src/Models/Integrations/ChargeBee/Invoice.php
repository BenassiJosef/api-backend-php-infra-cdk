<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/07/2017
 * Time: 10:10
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="customer_invoice", indexes={
 *     @ORM\Index(name="customerId", columns={"customerId"}),
 *     @ORM\Index(name="id", columns={"id"}),
 *     @ORM\Index(name="invoiceId", columns={"invoiceId"})
 * })
 * @ORM\Entity
 */
class Invoice
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="invoiceId", type="string", nullable=false)
     */
    private $invoice_id;

    /**
     * @var string
     * @ORM\Column(name="poNumber", length=100)
     */
    private $po_number;

    /**
     * @var string
     * @ORM\Column(name="customerId", length=50)
     */
    private $customer_id;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", length=50)
     */
    private $subscription_id;

    /**
     * @var boolean
     * @ORM\Column(name="recurring", type="boolean")
     */
    private $recurring;

    /**
     * @var string
     * @ORM\Column(name="status", length=50)
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="vatNumber", length=20)
     */
    private $vat_number;

    /**
     * @var string
     * @ORM\Column(name="priceType", length=20)
     */
    private $price_type;

    /**
     * @var integer
     * @ORM\Column(name="date", type="integer")
     */
    private $date;

    /**
     * @var integer
     * @ORM\Column(name="dueDate", type="integer")
     */
    private $due_date;

    /**
     * @var integer
     * @ORM\Column(name="netTermDays", type="integer")
     */
    private $net_term_days;

    /**
     * @var float
     * @ORM\Column(name="exchangeRate", type="float")
     */
    private $exchange_rate;

    /**
     * @var string
     * @ORM\Column(name="currencyCode", length=3)
     */
    private $currency_code;

    /**
     * @var integer
     * @ORM\Column(name="total", type="integer")
     */
    private $total;

    /**
     * @var integer
     * @ORM\Column(name="amountPaid", type="integer")
     */
    private $amount_paid;

    /**
     * @var integer
     * @ORM\Column(name="amountAdjusted", type="integer")
     */
    private $amount_adjusted;

    /**
     * @var integer
     * @ORM\Column(name="writeOffAmount", type="integer")
     */
    private $write_off_amount;

    /**
     * @var integer
     * @ORM\Column(name="creditsApplied", type="integer")
     */
    private $credits_applied;

    /**
     * @var integer
     * @ORM\Column(name="amountDue", type="integer")
     */
    private $amount_due;

    /**
     * @var integer
     * @ORM\Column(name="paidAt", type="integer")
     */
    private $paid_at;

    /**
     * @var string
     * @ORM\Column(name="dunningStatus", type="string", length=20)
     */
    private $dunning_status;

    /**
     * @var integer
     * @ORM\Column(name="nextRetryAt", type="integer")
     */
    private $next_retry_at;

    /**
     * @var integer
     * @ORM\Column(name="voidedAt", type="integer")
     */
    private $voided_at;

    /**
     * @var integer
     * @ORM\Column(name="updatedAt", type="integer")
     */
    private $updated_at;

    /**
     * @var integer
     * @ORM\Column(name="subTotal", type="integer")
     */
    private $sub_total;

    /**
     * @var integer
     * @ORM\Column(name="tax", type="integer")
     */
    private $tax;

    /**
     * @var string
     * @ORM\Column(name="resourceVersion", type="string")
     */
    private $resource_version;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var string
     * @ORM\Column(name="object", type="string")
     */
    private $object;

    /**
     * @var boolean
     * @ORM\Column(name="firstInvoice", type="boolean")
     */
    private $first_invoice;

    /**
     * @var string
     * @ORM\Column(name="newSalesAmount", type="string")
     */
    private $new_sales_amount;

    /**
     * @var boolean
     * @ORM\Column(name="hasAdvanceCharges", type="boolean")
     */
    private $has_advance_charges;

    /**
     * @var string
     * @ORM\Column(name="baseCurrencyCode", type="string")
     */
    private $base_currency_code;

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