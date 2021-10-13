<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPayments
 *
 * @ORM\Table(name="user_payments", indexes={@ORM\Index(name="serial", columns={"serial"})})
 * @ORM\Entity
 */
class UserPayments
{
    public function __construct(
        $email,
        $serial,
        $duration,
        $paymentAmount,
        $profileId,
        $devices,
        $planId,
        $creationdate = null
    ) {
        $this->email         = $email;
        $this->serial        = $serial;
        $this->duration      = $duration;
        $this->paymentAmount = $paymentAmount;
        $this->profileId     = $profileId;
        $this->devices       = $devices;
        $this->planId        = $planId;
        $this->status        = true;
        $this->creationdate  = is_null($creationdate) ? new \DateTime() : $creationdate;
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
     * @ORM\Column(name="email", type="string", length=50, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creationdate", type="datetime", nullable=true)
     */
    private $creationdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=255, nullable=true)
     */
    private $transactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_amount", type="string", length=255, nullable=true)
     */
    private $paymentAmount;

    /**
     * @var integer
     *
     * @ORM\Column(name="profileId", type="integer", nullable=true)
     */
    private $profileId;

    /**
     * @var integer
     *
     * @ORM\Column(name="devices", type="integer", nullable=false)
     */

    private $devices;

    /**
     * @var string
     *
     * @ORM\Column(name="planId", type="string", length=36)
     */

    private $planId;

    /**
     * @var string
     * @ORM\Column(name="reason", type="string")
     */
    private $reason;

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

