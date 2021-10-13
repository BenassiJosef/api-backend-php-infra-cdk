<?php

namespace App\Models;

use App\Package\Profile\MinimalUserProfile;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Query\Mysql\Date;
use Stripe\JsonSerializable;

/**
 * UserProfile
 *
 * @ORM\Table(name="user_profile", indexes={
 *     @ORM\Index(name="birthInfo", columns={"birthDay", "birthMonth"}),
 *     @ORM\Index(name="country", columns={"country", "countryCode"}),
 *     @ORM\Index(name="email", columns={"email"}),
 *     @ORM\Index(name="id", columns={"id"}),
 *     @ORM\Index(name="name", columns={"first", "last"}),
 *     @ORM\Index(name="phone", columns={"phone","phoneValid"})
 * }, options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity
 */
class UserProfile implements JsonSerializable, MinimalUserProfile
{

	public function __construct()
	{
		$this->timestamp = new DateTime();
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
	 *
	 * @ORM\Column(name="email", type="string", length=255, nullable=true)
	 */
	private $email;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="first", type="string", length=50, nullable=true)
	 */
	private $first;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="last", type="string", length=50, nullable=true)
	 */
	private $last;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phone", type="string", length=20, nullable=true)
	 */
	private $phone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phoneCC", type="string", length=3, nullable=true)
	 */
	private $phonecc;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="postcode", type="string", length=10, nullable=true)
	 */
	private $postcode;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="postcode_valid", type="boolean", nullable=true)
	 */
	private $postcodeValid;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="phone_valid", type="boolean", nullable=true)
	 */
	private $phoneValid;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="opt", type="boolean", nullable=true)
	 */
	private $opt;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="age", type="string", length=10, nullable=true)
	 */
	private $age;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="birth_month", type="integer", nullable=true)
	 */
	private $birthMonth;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="birth_day", type="integer", nullable=true)
	 */
	private $birthDay;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="age_range", type="string", length=12, nullable=true)
	 */
	private $ageRange;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="gender", type="string", length=1, nullable=true)
	 */
	private $gender;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="verified", type="boolean", nullable=false)
	 */
	private $verified = false;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="verified_id", type="string", length=128, nullable=true)
	 */
	private $verified_id;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="timestamp", type="datetime", nullable=false)
	 */
	private $timestamp;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="lat", type="float", precision=10, scale=6, nullable=true)
	 */
	private $lat;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="lng", type="float", precision=10, scale=6, nullable=true)
	 */
	private $lng;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="updated", type="datetime", nullable=false)
	 */
	private $updated;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="country", type="string", length=50, nullable=true)
	 */
	private $country;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="country_code", type="string", length=3, nullable=true)
	 */
	private $countryCode;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="custom", type="json_array", length=65535, nullable=true)
	 */
	private $custom;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string|null
	 */
	public function getEmail(): ?string
	{
		return $this->email;
	}

	/**
	 * @return string|null
	 */
	public function getFirst(): ?string
	{
		return $this->first;
	}


	/**
	 * @return string|null
	 */
	public function getLast(): ?string
	{
		return $this->last;
	}

	/**
	 * @return string
	 */
	public function getPhone(): ?string
	{
		return $this->phone;
	}

	/**
	 * @return string
	 */
	public function getPhonecc(): string
	{
		return $this->phonecc;
	}

	/**
	 * @return string
	 */
	public function getPostcode(): ?string
	{
		return $this->postcode;
	}

	/**
	 * @return bool
	 */
	public function isPostcodeValid(): bool
	{
		return $this->postcodeValid;
	}

	/**
	 * @return bool
	 */
	public function isPhoneValid(): bool
	{
		return $this->phoneValid;
	}

	/**
	 * @return bool
	 */
	public function getOpt(): ?bool
	{
		return $this->opt;
	}

	/**
	 * @return string
	 */
	public function getAge(): string
	{
		return $this->age;
	}

	/**
	 * @return int
	 */
	public function getBirthMonth(): ?int
	{
		return $this->birthMonth;
	}

	/**
	 * @return int
	 */
	public function getBirthDay(): ?int
	{
		return $this->birthDay;
	}

	/**
	 * @return string
	 */
	public function getAgeRange(): string
	{
		return $this->ageRange;
	}

	/**
	 * @return string
	 */
	public function getGender(): ?string
	{
		return $this->gender;
	}

	/**
	 * @return bool
	 */
	public function getVerified(): bool
	{
		return $this->verified;
	}

	/**
	 * @return string
	 */
	public function getVerifiedId(): string
	{
		return $this->verified_id;
	}

	/**
	 * @return DateTime
	 */
	public function getTimestamp(): DateTime
	{
		return $this->timestamp;
	}

	/**
	 * @return float
	 */
	public function getLat(): float
	{
		return $this->lat;
	}

	/**
	 * @return float
	 */
	public function getLng(): float
	{
		return $this->lng;
	}

	/**
	 * @return DateTime
	 */
	public function getUpdated(): DateTime
	{
		return $this->updated;
	}

	/**
	 * @return string
	 */
	public function getCountry(): string
	{
		return $this->country;
	}

	/**
	 * @return string
	 */
	public function getCountryCode(): string
	{
		return $this->countryCode;
	}

	public function getFullName(): string
	{
		return "{$this->first} {$this->last}";
	}

	/**
	 * @param string $email
	 * @return UserProfile
	 */
	public function setEmail(string $email): UserProfile
	{
		$this->email       = $email;
		$this->verified_id = md5($email);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCustom(): array
	{
		return $this->custom ?? [];
	}

	/**
	 * @return array
	 */
	public function getCustomForSerial(string $serial): array
	{
		if (!array_key_exists($serial, $this->getCustom())) {
			return [];
		}
		return $this->getCustom()[$serial];
	}

	/**
	 * @param array $custom
	 * @return UserProfile
	 */
	public function setCustom(array $custom): UserProfile
	{
		$this->custom = $custom;
		return $this;
	}


	/**
	 * @param string $first
	 * @return UserProfile
	 */
	public function setFirst(string $first): UserProfile
	{
		$this->first = $first;
		return $this;
	}

	/**
	 * @param string $last
	 * @return UserProfile
	 */
	public function setLast(string $last): UserProfile
	{
		$this->last = $last;
		return $this;
	}

	/**
	 * @param string $phone
	 * @return UserProfile
	 */
	public function setPhone(?string $phone): UserProfile
	{
		$this->phone = $phone;
		return $this;
	}

	/**
	 * @param int $birthMonth
	 * @return UserProfile
	 */
	public function setBirthMonth(?int $birthMonth): UserProfile
	{
		$this->birthMonth = $birthMonth;
		return $this;
	}

	/**
	 * @param int $birthDay
	 * @return UserProfile
	 */
	public function setBirthDay(?int $birthDay): UserProfile
	{
		$this->birthDay = $birthDay;
		return $this;
	}

	/**
	 * @param string $gender
	 * @return UserProfile
	 */
	public function setGender(?string $gender): UserProfile
	{
		$this->gender = $gender;
		return $this;
	}

	/**
	 * @param DateTime $timestamp
	 * @return UserProfile
	 */
	public function setTimestamp(DateTime $timestamp): UserProfile
	{
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * @param string $verified_id
	 * @return UserProfile
	 */
	public function setVerifiedId(string $verified_id): UserProfile
	{
		$this->verified_id = $verified_id;
		return $this;
	}

	public function validate()
	{
		$this->verified = true;
		$this->updated  = new DateTime();
	}

	/**
	 * @param DateTime $updated
	 * @return UserProfile
	 */
	public function setUpdated(DateTime $updated): UserProfile
	{
		$this->updated = $updated;
		return $this;
	}

	/**
	 * @return array[]
	 */
	public function emailSendTo(): array
	{
		return [
			[
				'to'   => $this->getEmail(),
				'name' => $this->getFullName(),
			]
		];
	}

	public function jsonSerialize()
	{
		return [
			'id'         => $this->getId(),
			'first'      => $this->getFirst(),
			'last'       => $this->getLast(),
			'email'      => $this->getEmail(),
			'verified'   => $this->getVerified(),
			'phone'      => $this->getPhone(),
			'gender'     => $this->getGender(),
			'postcode'   => $this->getPostcode(),
			'birthDay'   => $this->getBirthDay(),
			'birthMonth' => $this->getBirthMonth(),
			'opt' => $this->getOpt()
		];
	}

	public function zapierSerialize(string $serial = '')
	{
		return array_merge($this->jsonSerialize(), $this->getCustomForSerial($serial));
	}

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
		if ($value === null) {
			return;
		}
		if ($property === 'email') {
			$this->setEmail($value);
			return;
		}
		$this->$property = $value;
	}
}
