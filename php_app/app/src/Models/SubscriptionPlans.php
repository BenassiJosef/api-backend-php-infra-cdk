<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 13/02/2017
 * Time: 10:55
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SubscriptionPlans
 * @package App\Models
 *
 * @ORM\Table(name="subscription_plans")
 * @ORM\Entity
 */
class SubscriptionPlans
{
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
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="price", type="integer")
     */

    private $price;

    /**
     * @var string
     * @ORM\Column(name="description", type="string")
     */

    private $description;

    /**
     * @var string
     * @ORM\Column(name="stripe_id", type="string")
     */
    private $stripeId;

    /**
     * @var string
     * @ORM\Column(name="chargeBeeId", type="string")
     */
    private $chargeBeeId;

    /**
     * @var boolean
     * @ORM\Column(name="visible", type="boolean")
     */
    private $visible;

    /**
     * @var integer
     * @ORM\Column(name="units_per_sale", type="integer")
     */
    private $units;

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