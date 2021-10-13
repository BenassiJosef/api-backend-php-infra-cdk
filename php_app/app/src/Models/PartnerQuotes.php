<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 10/02/2017
 * Time: 21:21
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quotes
 *
 * @ORM\Table(options={"collate"="utf8"}, name="partner_quotes")
 * @ORM\Entity
 */
class PartnerQuotes
{

    public function __construct($customer, $reference, $description, $reseller, $total, $annual)
    {
        $this->customer    = $customer;
        $this->ref         = $reference;
        $this->description = $description;
        $this->reseller    = $reseller;
        $this->total       = $total;
        $this->created     = new \DateTime();
        $this->annual      = $annual;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="qu_id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $qu_id = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="ref", type="string", length=50, nullable=true)
     */
    private $ref;

    /**
     * @var string
     * @ORM\Column(name="customer", type="string", length=36, nullable=false)
     */
    private $customer;

    /**
     * @var string
     * @ORM\Column(name="reseller", type="string", length=36, nullable=false)
     */
    private $reseller;

    /**
     * @var integer
     *
     * @ORM\Column(name="charge", type="integer")
     */
    private $charge = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total", type="integer")
     */
    private $total = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean")
     */
    private $paid = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="completed", type="boolean")
     */
    private $completed = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="declined", type="boolean")
     */
    private $declined = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepted", type="boolean")
     */
    private $accepted = 0;

    /**
     * @var string
     * @ORM\Column(name="body", type="string")
     */
    private $body;

    /**
     * @var boolean
     * @ORM\Column(name="sent", type="boolean")
     */

    private $sent = 0;

    /**
     * @var boolean
     * @ORM\Column(name="sendQuoteAuto", type="boolean")
     */
    private $sendQuoteAuto = 0;

    /**
     * @var boolean
     * @ORM\Column(name="sendReference", type="boolean")
     */

    private $sendReference = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    private $description;


    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=512, nullable=true)
     */
    private $reason;

    /**
     * @var boolean
     *
     * @ORM\Column(name="annual", type="boolean")
     */
    private $annual;

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
