<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 05/07/2017
 * Time: 12:02
 */

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriptions
 *
 * @ORM\Table(name="subscriptions_addons")
 * @ORM\Entity
 */
class SubscriptionsAddon
{
    public function __construct(string $subscriptionId, string $addOnId, int $quantity, int $unitPrice)
    {
        $this->subscription_id = $subscriptionId;
        $this->add_on_id       = $addOnId;
        $this->quantity        = $quantity;
        $this->unit_price      = $unitPrice;
        $this->is_deleted      = false;
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
     * @ORM\Column(name="subscriptionId", type="string")
     */
    private $subscription_id;

    /**
     * @var string
     * @ORM\Column(name="addonId", type="string")
     */
    private $add_on_id;

    /**
     * @var integer
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var integer
     * @ORM\Column(name="unitPrice", type="integer")
     */
    private $unit_price;

    /**
     * @var boolean
     * @ORM\Column(name="isDeleted", type="boolean")
     */
    private $is_deleted;

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