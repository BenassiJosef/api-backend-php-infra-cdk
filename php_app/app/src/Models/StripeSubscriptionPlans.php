<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 13/02/2017
 * Time: 11:36
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class StripeSubscriptionPlans
 * @package App\Models
 * @ORM\Table(name="stripe_subscriptions_plans")
 * @ORM\Entity
 */
class StripeSubscriptionPlans
{

    public function __construct($subscriptionId, $subItemId, $planId)
    {
        $this->subscriptionId     = $subscriptionId;
        $this->subscriptionItemId = $subItemId;
        $this->planId             = $planId;
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
     * @ORM\Column(name="subscription_id", type="string")
     */

    private $subscriptionId;

    /**
     * @var string
     * @ORM\Column(name="subscription_item_id", type="string")
     */

    private $subscriptionItemId;

    /**
     * @var string
     * @ORM\Column(name="plan", type="string")
     */

    private $planId;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean")
     */

    private $active = 1;

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