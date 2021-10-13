<?php


namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkSettings
 *
 * @ORM\Table(name="network_settings", indexes={
 *     @ORM\Index(name="serial", columns={"serial"})
 * })
 * @ORM\Entity
 */
class NetworkSettings
{
    public function __construct(
        $serial,
        $other,
        $branding,
        $wifi,
        $facebook,
        $freeQuestions,
        $schedule,
        $url,
        $location,
        $type
    ) {
        $this->serial        = $serial;
        $this->other         = $other;
        $this->branding      = $branding;
        $this->wifi          = $wifi;
        $this->facebook      = $facebook;
        $this->freeQuestions = $freeQuestions;
        $this->schedule      = $schedule;
        $this->url           = $url;
        $this->location      = $location;
        $this->type          = $type;
        $this->hideFooter    = false;
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
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="alias", type="string", length=100, nullable=true)
     */
    private $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="branding", type="json_array", length=65535, nullable=true)
     */
    private $branding;

    /**
     * @var string
     *
     * @ORM\Column(name="other", type="json_array", length=65535, nullable=true)
     */
    private $other;

    /**
     * @var string
     *
     * @ORM\Column(name="pricing", type="json_array", length=65535, nullable=true)
     */
    private $pricing;

    /**
     * @var integer
     *
     * @ORM\Column(name="devices", type="integer", nullable=true)
     */
    private $devices;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=200, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=2048, nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="wifi", type="json_array", length=65535, nullable=true)
     */
    private $wifi;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="json_array", length=65535, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook", type="json_array", length=65535, nullable=true)
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
     * @ORM\Column(name="custom_questions", type="json_array", length=65535, nullable=true)
     */
    private $customQuestions;

    /**
     * @var string
     *
     * @ORM\Column(name="free_questions", type="json_array", length=65535, nullable=true)
     */
    private $freeQuestions;

    /**
     * @var string
     *
     * @ORM\Column(name="integrations", type="json_array", length=65535, nullable=true)
     */
    private $integrations;

    /**
     * @var string
     *
     * @ORM\Column(name="paypalAccount", type="string", length=36, nullable=true)
     */
    private $paypalAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="schedule", type="json_array", length=65535, nullable=true)
     */
    private $schedule;

    /**
     * @var integer
     *
     * @ORM\Column(name="v", type="integer", nullable=true)
     */
    private $v = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="validation_timeout", type="integer", nullable=true)
     */
    private $validationTimeout;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency = 'GBP';

    /**
     * @var boolean
     *
     * @ORM\Column(name="translation", type="boolean", nullable=true)
     */
    private $translation = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=2, nullable=true)
     */
    private $language = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_connect_id", type="string", length=100, nullable=true)
     */
    private $stripe_connect_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="paymentType", type="string", length=10, nullable=true)
     */
    private $paymentType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hide_footer", type="boolean")
     */
    private $hideFooter;

    /**
     * @var string
     *
     * @ORM\Column(name="locationType", type="string", length=10, nullable=true)
     */
    private $locationType;

    /**
     * @var string
     * @ORM\Column(name="paypal_api_user", type="string")
     */
    private $paypal_api_user;

    /**
     * @var string
     * @ORM\Column(name="paypal_api_pass", type="string")
     */
    private $paypal_api_pass;

    /**
     * @var string
     * @ORM\Column(name="paypal_api_sig", type="string")
     */
    private $paypal_api_sig;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * Get array copy of object
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function &__get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

}

