<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="invoice")
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
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_id", type="string", length=100, nullable=true)
     */
    private $invoiceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="date_from", type="integer", nullable=true)
     */
    private $dateFrom;

    /**
     * @var integer
     *
     * @ORM\Column(name="date_to", type="integer", nullable=true)
     */
    private $dateTo;

    /**
     * @var string
     *
     * @ORM\Column(name="customer", type="string", length=100, nullable=true)
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(name="plan", type="string", length=20, nullable=true)
     */
    private $plan;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=true)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="net_amount", type="float", precision=10, scale=0, nullable=true)
     */
    private $netAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean", nullable=true)
     */
    private $paid;

    /**
     * @var integer
     *
     * @ORM\Column(name="tax", type="integer", nullable=true)
     */
    private $tax = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="tax_amount", type="integer", nullable=true)
     */
    private $taxAmount;

    /**
     * @var integer
     *
     * @ORM\Column(name="tax_percent", type="integer", nullable=true)
     */
    private $taxPercent;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=5, nullable=true)
     */
    private $currency;


}

