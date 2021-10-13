<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 09/10/2017
 * Time: 16:08
 */

namespace App\Models\Integrations\HubSpot;

use Doctrine\ORM\Mapping as ORM;

/**
 * HubSpotContact
 *
 * @ORM\Table(name="hubspot_contact")
 * @ORM\Entity
 */
class HubSpotContact
{
    public function __construct(string $vId, string $email)
    {
        $this->id           = $vId;
        $this->emailAddress = $email;
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="emailAddress", type="string", nullable=false)
     */
    private $emailAddress;

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