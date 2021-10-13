<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 21/07/2017
 * Time: 10:47
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="customer_invoice_discount")
 * @ORM\Entity
 */
class InvoiceDiscount
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
     * @ORM\Column(name="invoiceId", length=50, nullable=false)
     */
    private $invoice_id;

    /**
     * @var string
     * @ORM\Column(name="object", type="string")
     */
    private $object;

    /**
     * @var string
     * @ORM\Column(name="entityType", type="string")
     */
    private $entity_type;

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
     * @var string
     * @ORM\Column(name="entityId", type="string")
     */
    private $entity_id;

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