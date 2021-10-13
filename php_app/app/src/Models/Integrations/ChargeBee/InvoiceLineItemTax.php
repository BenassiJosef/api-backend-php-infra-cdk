<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 31/07/2017
 * Time: 16:46
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="customer_invoice_line_item_taxes")
 * @ORM\Entity
 **/

class InvoiceLineItemTax
{
    /**
     * @var string
     *
     * @ORM\Column(name="invoiceLineItemId", type="string")
     * @ORM\Id
     */
    private $line_item_id;

    /**
     * @var string
     * @ORM\Column(name="invoiceId", type="string")
     */
    private $invoice_id;

    /**
     * @var string
     * @ORM\Column(name="taxName", type="string")
     */
    private $tax_name;

    /**
     * @var float
     * @ORM\Column(name="taxRate", type="float")
     */
    private $tax_rate;

    /**
     * @var string
     * @ORM\Column(name="taxJurisType", type="string")
     */
    private $tax_juris_type;

    /**
     * @var string
     * @ORM\Column(name="taxJurisName", type="string")
     */
    private $tax_juris_name;

    /**
     * @var string
     * @ORM\Column(name="taxJurisCode", type="string")
     */
    private $tax_juris_code;

    /**
     * @var string
     * @ORM\Column(name="object", type="string")
     */
    private $object;

    /**
     * @var integer
     * @ORM\Column(name="taxAmount", type="integer")
     */
    private $tax_amount;

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