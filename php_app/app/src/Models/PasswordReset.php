<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 31/01/2017
 * Time: 10:36
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PasswordReset
 * @package App\Models
 *
 * @ORM\Table(name="password_reset")
 * @ORM\Entity
 */
class PasswordReset
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;


    /**
     * @var string
     * @ORM\Column(name="token", length=128, nullable=false)
     */

    private $token;

    /**
     * @var string
     * @ORM\Column(name="email", length=128, nullable=false)
     */

    private $email;

    /**
     * @var boolean
     * @ORM\Column(name="tokenValid", type="boolean")
     */

    private $valid = 1;

    /**
     * @var string
     * @ORM\Column(name="service", type="string")
     */
    private $service;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */

    private $createdAt;

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