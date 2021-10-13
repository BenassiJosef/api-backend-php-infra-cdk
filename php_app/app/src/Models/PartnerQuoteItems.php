<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 13/02/2017
 * Time: 23:27
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;


/**
 * Quotes
 *
 * @ORM\Table(options={"collate"="utf8"}, name="partner_quote_items")
 * @ORM\Entity
 */
class PartnerQuoteItems
{

    public function __construct($quoteId, $serial, $planId, $method)
    {
        $this->quote_id = $quoteId;
        $this->serial   = $serial;
        $this->planId  = $planId;
        $this->method   = $method;
        $this->paid = 0;
        $this->deleted  = 0;
    }

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
     * @ORM\Column(name="quote_id", type="string", length=36, nullable=true)
     */
    private $quote_id;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="plan_id", type="string", length=36, nullable=false)
     */
    private $planId;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string")
     */
    private $method;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean")
     */
    private $paid;


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
