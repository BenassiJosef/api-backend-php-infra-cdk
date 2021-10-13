<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 10:50
 */

namespace App\Models\Integrations\ChargeBee;
use Doctrine\ORM\Mapping as ORM;
/**
 * Credit Note Line Item Taxes
 *
 * @ORM\Table(name="customer_credit_note_line_item_taxes")
 * @ORM\Entity
 */
class CreditNoteLineItemTax
{
    /**
     * @var string
     *
     * @ORM\Column(name="lineItemId", type="string")
     * @ORM\Id
     */
    private $line_item_id;

    /**
     * @var string
     * @ORM\Column(name="creditNoteId", type="string")
     */
    private $credit_note_id;

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