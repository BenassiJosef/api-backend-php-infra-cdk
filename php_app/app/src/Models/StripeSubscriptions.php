<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/02/2017
 * Time: 17:07
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriptions
 *
 * @ORM\Table(name="stripe_subscriptions")
 * @ORM\Entity
 */
class StripeSubscriptions
{
    public function __construct($createdBy, $serial, $subscription, $startDate, $endDate, $status, $annual = false)
    {
        $this->createdBy         = $createdBy;
        $this->serial            = $serial;
        $this->subscriptionId    = $subscription;
        $this->startDate         = $startDate;
        $this->endDate           = $endDate;
        $this->cancelAtPeriodEnd = 0;
        $this->status            = $status;
        $this->createdAt         = new \DateTime();
        $this->active            = 1;
        $this->annual            = $annual;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';


    /**
     * @var string
     * @ORM\Column(name="created_by", type="string")
     */

    private $createdBy;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */

    private $serial;

    /**
     * @var string
     * @ORM\Column(name="stripe_subscription_id", type="string")
     */

    private $subscriptionId;

    /**
     * @var integer
     * @ORM\Column(name="start_date", type="integer")
     */

    private $startDate;

    /**
     * @var integer
     * @ORM\Column(name="end_date", type="integer")
     */

    private $endDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cancel_at_end_date", type="boolean", nullable=true)
     */
    private $cancelAtPeriodEnd;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = true;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */

    private $createdAt;

    /**
     * @var string
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var boolean
     * @ORM\Column(name="billedAnnually", type="boolean")
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