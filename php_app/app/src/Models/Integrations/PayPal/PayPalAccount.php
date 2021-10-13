<?php
/**
 * Created by jamieaitken on 04/12/2017 at 09:48
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations\PayPal;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * PayPalAccount
 *
 * @ORM\Table(name="paypal_account")
 * @ORM\Entity
 */
class PayPalAccount
{

    public function __construct(string $name, string $username, string $password, string $signature)
    {
        $this->id        = Strings::idGenerator('ppac');
        $this->name      = $name;
        $this->username  = $username;
        $this->password  = $password;
        $this->signature = $signature;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", nullable=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", nullable=false)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="string", nullable=false)
     */
    private $signature;

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