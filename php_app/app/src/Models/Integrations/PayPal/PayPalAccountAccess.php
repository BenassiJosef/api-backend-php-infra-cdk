<?php
/**
 * Created by jamieaitken on 04/12/2017 at 09:57
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\PayPal;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * PayPalAccountAccess
 *
 * @ORM\Table(name="paypal_account_access")
 * @ORM\Entity
 */
class PayPalAccountAccess
{

    public function __construct(Organization $organization, string $paypalAccount)
    {

        $this->paypalAccount = $paypalAccount;
        $this->uid = $organization->getOwnerId()->toString();
        $this->organizationId = $organization->getId();
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
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="paypalAccount", type="string")
     */
    private $paypalAccount;


    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

    /**
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