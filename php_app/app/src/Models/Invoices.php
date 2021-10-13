<?php

namespace App\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="invoices")
 * @ORM\Entity
 */
class Invoices
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_prefix", type="string", length=5)
     */
    private $id_prefix = 'INVS-';

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_id", type="string", length=27)
     */
    private $invoice_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount_due", type="integer")
     */
    private $amount_due = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="application_fee", type="integer")
     */
    private $application_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="attempt_count", type="integer")
     */
    private $attempt_count = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="attempted", type="boolean")
     */
    private $attempted = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="charge", type="integer")
     */
    private $charge;

    /**
     * @var boolean
     *
     * @ORM\Column(name="closed", type="boolean")
     */
    private $closed = false;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="customer", type="string", length=18)
     */
    private $customer;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="integer")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100)
     */
    private $description = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="integer")
     */
    private $discount;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount_amount", type="integer")
     */
    private $discount_amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="ending_balance", type="integer")
     */
    private $ending_balance = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="forgiven", type="boolean")
     */
    private $forgiven = false;


    /**
     * @var integer
     *
     * @ORM\Column(name="next_payment_attempt", type="integer")
     */
    private $next_payment_attempt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean")
     */
    private $paid = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="livemode", type="boolean")
     */
    private $livemode = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="period_start", type="integer")
     */
    private $period_start;

    /**
     * @var integer
     *
     * @ORM\Column(name="period_end", type="integer")
     */
    private $period_end;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_number", type="string", length=10)
     */
    private $receipt_number;

    /**
     * @var integer
     *
     * @ORM\Column(name="starting_balance", type="integer")
     */
    private $starting_balance = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="statement_descriptor", type="string", length=20)
     */
    private $statement_descriptor;

    /**
     * @var string
     *
     * @ORM\Column(name="subscription", type="string", length=18)
     */
    private $subscription;

    /**
     * @var integer
     *
     * @ORM\Column(name="subtotal", type="integer", nullable=false)
     */
    private $subtotal = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tax", type="integer")
     */
    private $tax = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tax_percent", type="integer")
     */
    private $tax_percent = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total", type="integer")
     */
    private $total = 0;

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

