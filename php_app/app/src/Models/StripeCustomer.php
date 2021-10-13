<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 03/01/2017
 * Time: 12:52
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * stripeCustomer
 *
 * @ORM\Table(name="stripeCustomer")
 * @ORM\Entity
 */
class StripeCustomer
{

    public function __construct($profileId, $stripeCustomerId, $stripeUserId)
    {
        $this->profileId        = $profileId;
        $this->stripeCustomerId = $stripeCustomerId;
        $this->stripe_user_id   = $stripeUserId;
        $this->created          = new \DateTime();
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
     * @var integer
     *
     * @ORM\Column(name="profileId", type="integer", length=11, nullable=false)
     */
    private $profileId;

    /**
     * @var string
     * @ORM\Column(name="stripeCustomerId", type="string", length=38, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @var string
     * @ORM\Column(name="stripe_user_id", type="string", length=38, nullable=true)
     */
    private $stripe_user_id;

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;


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