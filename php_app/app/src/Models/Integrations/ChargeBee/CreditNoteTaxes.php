<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 10:46
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit Note Taxes
 *
 * @ORM\Table(name="customer_credit_note_taxes")
 * @ORM\Entity
 */
class CreditNoteTaxes
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
     * @ORM\Column(name="creditNoteId", type="string")
     */
    private $credit_note_id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @var string
     * @ORM\Column(name="description", type="string")
     */
    private $description;

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