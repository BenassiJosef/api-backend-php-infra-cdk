<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/10/2017
 * Time: 09:15
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Credit Note Taxes
 *
 * @ORM\Table(name="failed_payments")
 * @ORM\Entity
 */
class FailedTransaction
{

    public function __construct(
        string $customerId,
        string $invoiceId,
        string $transactionId,
        string $reasonCode,
        string $reasonText,
        int $totalBeingLost
    ) {
        $this->customerId     = $customerId;
        $this->invoiceId      = $invoiceId;
        $this->timesTried     = 1;
        $this->transactionId  = $transactionId;
        $this->reasonCode     = $reasonCode;
        $this->reasonText     = $reasonText;
        $this->totalBeingLost = $totalBeingLost;
        $this->createdAt      = new \DateTime();
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
     * @ORM\Column(name="customerId", type="string")
     */
    private $customerId;

    /**
     * @var string
     *
     * @ORM\Column(name="invoiceId", type="string", nullable=false)
     */
    private $invoiceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="timesTried", type="integer", nullable=false)
     */
    private $timesTried;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="transactionId", type="string")
     */
    private $transactionId;

    /**
     * @var string
     * @ORM\Column(name="reasonCode", type="string")
     */
    private $reasonCode;

    /**
     * @var string
     * @ORM\Column(name="reasonText", type="string")
     */
    private $reasonText;

    /**
     * @var int
     * @ORM\Column(name="totalBeingLost", type="integer")
     */
    private $totalBeingLost;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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