<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 09:47
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit Note
 *
 * @ORM\Table(name="customer_credit_note")
 * @ORM\Entity
 */
class CreditNote
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     * */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="customerId", type="string")
     */
    private $customer_id;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", type="string")
     */
    private $subscription_id;

    /**
     * @var string
     * @ORM\Column(name="referenceInvoiceId", type="string")
     */
    private $reference_invoice_id;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="reasonCode", type="string")
     */
    private $reason_code;

    /**
     * @var string
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var integer
     * @ORM\Column(name="date", type="integer")
     */
    private $date;

    /**
     * @var string
     * @ORM\Column(name="priceType", type="string")
     */
    private $price_type;

    /**
     * @var string
     * @ORM\Column(name="currencyCode", type="string")
     */
    private $currency_code;

    /**
     * @var integer
     * @ORM\Column(name="total", type="integer")
     */
    private $total;

    /**
     * @var integer
     * @ORM\Column(name="amountAllocated", type="integer")
     */
    private $amount_allocated;

    /**
     * @var integer
     * @ORM\Column(name="amountRefunded", type="integer")
     */
    private $amount_refunded;

    /**
     * @var integer
     * @ORM\Column(name="amountAvailable", type="integer")
     */
    private $amount_available;

    /**
     * @var integer
     * @ORM\Column(name="refundedAt", type="integer")
     */
    private $refunded_at;

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