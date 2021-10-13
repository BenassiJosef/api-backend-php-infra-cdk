<?php

/**
 * Created by jamieaitken on 02/03/2018 at 09:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations;

use App\Models\Integrations\PayPal\PayPalAccount;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Models\Organization;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use JsonSerializable;

/**
 * LocationSettings
 *
 * @ORM\Table(name="location_settings")
 * @ORM\Entity
 */
class LocationSettings implements JsonSerializable
{
	public static $keys = [
		'id',
		'serial',
		'alias',
		'branding',
		'other',
		'url',
		'wifi',
		'location',
		'facebook',
		'type',
		'paypalAccount',
		'schedule',
		'currency',
		'translation',
		'language',
		'stripe_connect_id',
		'paymentType',
		'createdAt',
		'demoData'
	];

	public function __construct(
		string $serial,
		LocationOther $other,
		LocationBranding $branding,
		LocationWiFi $wifi,
		LocationPosition $location,
		LocationSocial $facebook,
		string $schedule,
		string $url,
		$freeQuestions,
		Organization $organization = null
	) {
		$this->serial        = $serial;
		$this->otherSettings         = $other;
		$this->brandingSettings      = $branding;
		$this->wifiSettings          = $wifi;
		$this->locationSettings      = $location;
		$this->facebookSettings      = $facebook;
		$this->schedule      = $schedule;
		$this->url           = $url;
		$this->freeQuestions = $freeQuestions;
		$this->type          = 0;
		$this->createdAt     = new \DateTime();
		$this->access        = new ArrayCollection();
		if ($organization !== null) {
			$this->organization   = $organization;
			$this->organizationId = $organization->getId();
		}
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
	 * @ORM\Column(name="organization_id", type="uuid", nullable=true)
	 * @var UuidInterface | null $organizationId
	 */
	private $organizationId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Organization", cascade={"persist"})
	 * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
	 * @var Organization | null $organization
	 */
	private $organization;

	/**
	 * @var string
	 * @ORM\Column(name="alias", type="string", length=100, nullable=true)
	 */
	private $alias;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="branding", type="string")
	 */
	private $branding;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Locations\WiFi\LocationWiFi", cascade={"persist"})
	 * @ORM\JoinColumn(name="wifi", referencedColumnName="id", nullable=false)
	 * @var LocationWiFi | null $wifiSettings
	 */
	private $wifiSettings;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="other", type="string")
	 */
	private $other;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Locations\Other\LocationOther", cascade={"persist"})
	 * @ORM\JoinColumn(name="other", referencedColumnName="id", nullable=false)
	 * @var LocationOther | null $otherSettings
	 */
	private $otherSettings;



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
	 * @ORM\ManyToOne(targetEntity="App\Models\Locations\Branding\LocationBranding", cascade={"persist"})
	 * @ORM\JoinColumn(name="branding", referencedColumnName="id", nullable=false)
	 * @var LocationBranding | null $brandingSettings 
	 */
	private $brandingSettings;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="location", type="string")
	 */
	private $location;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Locations\Position\LocationPosition", cascade={"persist"})
	 * @ORM\JoinColumn(name="location", referencedColumnName="id", nullable=false)
	 * @var LocationPosition | null $locationSettings
	 */
	private $locationSettings;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="facebook", type="string")
	 */
	private $facebook;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Locations\Social\LocationSocial", cascade={"persist"})
	 * @ORM\JoinColumn(name="facebook", referencedColumnName="id", nullable=false)
	 * @var LocationSocial | null $facebookSettings
	 */
	private $facebookSettings;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="type", type="integer", nullable=true)
	 */
	private $type;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="customQuestions", type="json_array", length=65535, nullable=true)
	 */
	private $customQuestions;

	/**
	 * @var array
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
	 * @ORM\ManyToOne(targetEntity="App\Models\Integrations\PayPal\PayPalAccount", cascade={"persist"})
	 * @ORM\JoinColumn(name="paypalAccount", referencedColumnName="id", nullable=false)
	 * @var PayPalAccount | null $paypalAccountSettings
	 */
	private $paypalAccountSettings;



	/**
	 * @var string
	 *
	 * @ORM\Column(name="schedule", type="string")
	 */
	private $schedule;

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

	/**
	 * @var \DateTime
	 * @ORM\Column(name="createdAt", type="datetime")
	 */
	private $createdAt;

	/**
	 * @var boolean
	 * @ORM\Column(name="demoData", type="boolean")
	 */
	private $demoData;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getSerial(): string
	{
		return $this->serial;
	}

	/**
	 * @return UuidInterface|null
	 */
	public function getOrganizationId(): ?UuidInterface
	{
		return $this->organizationId;
	}

	/**
	 * @return Organization|null
	 */
	public function getOrganization(): ?Organization
	{
		return $this->organization;
	}

	/**
	 * @param Organization|null $organization
	 * @return LocationSettings
	 */
	public function setOrganization(Organization $organization): LocationSettings
	{
		$this->organization   = $organization;
		$this->organizationId = $organization->getId();
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}

	/**
	 * @return string
	 */
	public function getBranding(): string
	{
		return $this->branding;
	}

	/**
	 * @return string
	 */
	public function getOther(): string
	{
		return $this->other;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getWifi(): string
	{
		return $this->wifi;
	}

	/**
	 * @return string
	 */
	public function getLocation(): ?string
	{
		return $this->location;
	}

	/**
	 * @return string
	 */
	public function getFacebook(): string
	{
		return $this->facebook;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getNiceType(): string
	{
		if ($this->type === 0) {
			return 'free';
		}
		if ($this->type === 1) {
			return 'paid';
		}
		if ($this->type === 2) {
			return 'hybrid';
		}
	}

	public function setType(int $type)
	{
		$this->type = $type;
	}

	/**
	 * @return array
	 */
	public function getCustomQuestions(): array
	{
		return $this->customQuestions ?? [];
	}

	/**
	 * @return array
	 */
	public function getFreeQuestions(): array
	{
		return $this->freeQuestions ?? ["Email"];
	}

	/**
	 * @return string
	 */
	public function getPaypalAccount(): string
	{
		return $this->paypalAccount;
	}

	/**
	 * @return string
	 */
	public function getSchedule(): string
	{
		return $this->schedule;
	}

	/**
	 * @return string
	 */
	public function getCurrency(): string
	{
		return $this->currency;
	}

	/**
	 * @return bool
	 */
	public function isTranslation(): bool
	{
		return $this->translation;
	}

	/**
	 * @return string
	 */
	public function getLanguage(): string
	{
		return $this->language;
	}

	/**
	 * @return string
	 */
	public function getStripeConnectId(): string
	{
		return $this->stripe_connect_id;
	}

	/**
	 * @return string
	 */
	public function getPaymentType(): string
	{
		return $this->paymentType;
	}

	/**
	 * @return bool
	 */
	public function getUsingStripe(): bool
	{
		return strpos($this->paymentType, 'stripe') !== false;
	}

	/**
	 * @return bool
	 */
	public function getUsingPaypal(): bool
	{
		return strpos($this->paymentType, 'paypal') !== false;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt(): \DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @return bool
	 */
	public function isDemoData(): bool
	{
		return $this->demoData;
	}

	public static function defaultUrl()
	{
		return 'https://stampede.ai/now-online';
	}

	public static function defaultType()
	{
		return 0;
	}

	public static function defaultFreeQuestions()
	{
		return ['Email'];
	}

	public static function defaultTranslation()
	{
		return false;
	}

	public static function defaultLanguage()
	{
		return 'en';
	}

	public function getBrandingSettings(): LocationBranding
	{
		return $this->brandingSettings;
	}


	public function getOtherSettings(): LocationOther
	{
		return $this->otherSettings;
	}

	public function getFacebookSettings(): LocationSocial
	{
		return $this->facebookSettings;
	}

	public function getPaypalAccountSettings(): PayPalAccount
	{
		return $this->paypalAccountSettings;
	}



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

	public function getPartialBranding()
	{
		if (is_null($this->brandingSettings)) {
			return null;
		}
		return $this->brandingSettings->partial();
	}

	public function getPartialAddress()
	{
		if (is_null($this->locationSettings)) {
			return null;
		}
		return $this->locationSettings->partial();
	}

	public function jsonSerialize()
	{
		return [
			"id" => $this->getId(),
			"serial" => $this->getSerial(),
			"alias" => $this->getAlias(),
			"url" => $this->getUrl(),
			"location" => $this->getLocation(),
			"currency" => $this->getCurrency(),
			"language" => $this->getLanguage(),
			"type" => $this->getType(),
			"nice_type" => $this->getNiceType(),
			"translation" => $this->isTranslation(),
			"free_questions" => $this->getFreeQuestions(),
			"custom_questions" => $this->getCustomQuestions(),
			"landing_url" => $this->getUrl(),
			"payment_options" => [
				'stripe' => $this->getUsingStripe(),
				'paypal' => $this->getUsingPaypal()
			],
			"branding" => $this->getPartialBranding(),
			"address" => $this->getPartialAddress(),
			'organization_id' => $this->getOrganizationId()
		];
	}

	public function jsonSerializeMapped()
	{
		return array_merge(
			$this->jsonSerialize(),
			[
				'address' => $this->locationSettings->jsonSerialize(),
				'branding' => $this->brandingSettings->jsonSerialize(),
				'wifi' => $this->wifiSettings->jsonSerialize(),
				'other' => $this->otherSettings->jsonSerialize(),
				'social' => $this->facebookSettings->jsonSerialize()
			]
		);
	}
}
