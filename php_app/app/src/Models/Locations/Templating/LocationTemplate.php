<?php
/**
 * Created by jamieaitken on 24/07/2018 at 11:24
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Templating;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationTemplate
 *
 * @ORM\Table(name="location_template")
 * @ORM\Entity
 */
class LocationTemplate
{

    public function __construct()
    {

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
     * @ORM\Column(name="serial", type="string", length=12)
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="serialCopyingFrom", type="string", length=12)
     */
    private $serialCopyingFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="branding", type="string")
     */
    private $branding;

    /**
     * @var string
     *
     * @ORM\Column(name="other", type="string")
     */
    private $other;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="wifi", type="string")
     */
    private $wifi;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string")
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="string")
     */
    private $facebook;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="customQuestions", type="json_array", length=65535, nullable=true)
     */
    private $customQuestions;

    /**
     * @var string
     *
     * @ORM\Column(name="freeQuestions", type="json_array", length=65535, nullable=true)
     */
    private $freeQuestions;

    /**
     * @var string
     *
     * @ORM\Column(name="paypalAccount", type="string", length=36, nullable=true)
     */
    private $paypalAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="schedule", type="string")
     */
    private $schedule;

    /**
     * @var boolean
     *
     * @ORM\Column(name="translation", type="boolean", nullable=true)
     */
    private $translation = false;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string")
     */
    private $language = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="stripeConnectId", type="string", length=100, nullable=true)
     */
    private $stripe_connect_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="paymentType", type="string", length=10, nullable=true)
     */
    private $paymentType;


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