<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/03/2017
 * Time: 10:48
 */

namespace App\Models\Locations\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Campaign
 * @package App\Models\Locations\Marketing
 *
 * @ORM\Table(name="marketing_campaigns")
 * @ORM\Entity
 */
class Campaign
{
    public function __construct($name, $credits)
    {
        $this->name            = $name;
        $this->creditsAssigned = $credits;
        $this->active          = 1;
        $this->created         = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var string
     * @ORM\Column(name="eventId", type="string", length=36)
     */
    private $eventId;

    /**
     * @var string
     * @ORM\Column(name="messageId", type="string", length=36)
     */
    private $messageId;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=64)
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="creditsAssigned", type="integer")
     */
    private $creditsAssigned;

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