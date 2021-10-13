<?php
/**
 * Created by jamieaitken on 15/04/2018 at 14:07
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShortUrlEvent
 *
 * @ORM\Table(name="shortened_url_event")
 * @ORM\Entity
 */
class ShortUrlEvent
{

    public function __construct(string $shortUrlId, string $city, string $country)
    {
        $this->shortUrlId     = $shortUrlId;
        $this->city           = $city;
        $this->country        = $country;
        $this->eventCreatedAt = new \DateTime();
    }

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="shortUrlId", type="string")
     */
    private $shortUrlId;

    /**
     * @var string
     * @ORM\Column(name="country", type="string")
     */
    private $country;

    /**
     * @var string
     * @ORM\Column(name="city", type="string")
     */
    private $city;

    /**
     * @var \DateTime
     * @ORM\Column(name="eventCreatedAt", type="datetime")
     */
    private $eventCreatedAt;

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