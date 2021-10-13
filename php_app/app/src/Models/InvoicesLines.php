<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="invoices_lines")
 * @ORM\Entity
 */
class InvoicesLines
{

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="plan_id", type="string", length=10)
     */
    private $plan_id;

    /**
     * @var integer
     * @ORM\Column(name="invoice", type="integer", length=11)
     */
    private $invoice;

    /**
     * @var string
     *
     * @ORM\Column(name="line_id", type="string", length=27)
     */
    private $line_id;

    /**
     * @var string
     *
     * @ORM\Column(name="subscription", type="string", length=18)
     */
    private $subscription;

    /**
     * @var string
     *
     * @ORM\Column(name="subscription_item", type="string", length=27)
     */
    private $subscription_item;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="discountable", type="boolean")
     */
    private $discountable = false;

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
     * @ORM\Column(name="name", type="string", length=18)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="statement_descriptor", type="string", length=20)
     */
    private $statement_descriptor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="proration", type="boolean")
     */
    private $proration = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity = 0;

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

