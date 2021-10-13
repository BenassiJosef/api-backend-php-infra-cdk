<?php

namespace App\Models;

use App\Models\Locations\LocationSettings;
use App\Package\Auth\UserSource;
use App\Package\Member\UserCreationInput;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Select;
use Ramsey\Uuid\Uuid;
use Slim\Collection;
use JsonSerializable;

/**
 * OauthUser
 *
 * @ORM\Table(name="oauth_users", indexes={@ORM\Index(name="email", columns={"email"})})
 * @ORM\Entity
 */
class OauthUser implements JsonSerializable, UserSource
{
	public static $KEYS = [
		'uid',
		'admin',
		'reseller',
		'email',
		'password',
		'company',
		'first',
		'last',
		'inChargeBee',
		'stripe_id',
		'role',
		'country'
	];

	public static $UPDATABLE_KEYS = [
		'email',
		'first',
		'last',
		'company',
		'role',
		'country',
		'edited'
	];

	public function __construct(
		string $email,
		string $password,
		?string $company,
		string $reseller,
		?string $firstName,
		?string $lastName,
		?string $stripeId = null
	) {
		$this->uid   = Uuid::uuid1();
		$this->email = $email;
		$this->setPassword($password);
		$this->company        = $company;
		$this->reseller       = $reseller;
		$this->first          = $firstName;
		$this->last           = $lastName;
		$this->stripe_id      = $stripeId;
		$this->inChargeBee    = 0;
		$this->created        = new DateTime();
		$this->country        = 'GB';
		$this->locationAccess = new ArrayCollection();
	}

	/**
	 * @var string
	 *
	 * @ORM\Column(name="uid", type="guid", length=36, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 */
	private $uid;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="admin", type="string", length=36, nullable=true)
	 */
	private $admin;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="reseller", type="string", length=36, nullable=true)
	 */
	private $reseller;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=100, nullable=false)
	 */
	private $email;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="password", type="string", length=100, nullable=false)
	 */
	private $password;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="company", type="string", length=36, nullable=true)
	 */
	private $company;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="first", type="string", length=18, nullable=true)
	 */
	private $first;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="last", type="string", length=18, nullable=true)
	 */
	private $last;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="inChargeBee", type="boolean")
	 */
	private $inChargeBee;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="stripe_id", type="string", length=18, nullable=true)
	 */
	private $stripe_id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="role", type="integer", nullable=true)
	 */
	private $role = 2;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="country", type="string", length=2, nullable=true)
	 */
	private $country;

	/**
	 * @ORM\OneToMany(targetEntity="App\Models\LocationAccess", mappedBy="user", cascade="persist")
	 * @var Collection | Selectable | LocationAccess[]
	 */
	private $locationAccess;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="created", type="datetime", nullable=false)
	 */
	private $created;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="edited", type="datetime", nullable=false)
	 */
	private $edited;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="deleted", type="boolean", nullable=true)
	 */
	private $deleted = 0;

	/**
	 * @var array
	 */
	private $access = [];

	/**
	 * @var Collection|Selectable|LocationAccess[]
	 */
	private $organisationAccess = [];

	/**
	 * @return OauthUser
	 */
	public function getUser(): OauthUser
	{
		return $this;
	}


	/**
	 * @return string
	 */
	public function getUserId(): string
	{
		return $this->uid;
	}

	/**
	 * @return string
	 */
	public function getUid(): string
	{
		return $this->uid;
	}

	/**
	 * @return string
	 */
	public function getAdmin(): ?string
	{
		return $this->admin;
	}

	/**
	 * @return string
	 */
	public function getReseller(): ?string
	{
		return $this->reseller;
	}

	/**
	 * @return LocationAccess[] | Selectable | Collection | ArrayCollection
	 */
	public function getLocationAccess()
	{
		return $this->locationAccess;
	}

	/**
	 * @return string
	 */
	public function getEmail(): ?string
	{
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getCompany(): ?string
	{
		return $this->company;
	}

	/**
	 * @return string
	 */
	public function getFirst(): ?string
	{
		return $this->first;
	}

	/**
	 * @return string
	 */
	public function getLast(): ?string
	{
		return $this->last;
	}

	/**
	 * @return bool
	 */
	public function isInChargeBee(): bool
	{
		return $this->inChargeBee;
	}

	/**
	 * @return string
	 */
	public function getStripeId(): ?string
	{
		return $this->stripe_id;
	}

	/**
	 * @return int
	 */
	public function getRole(): int
	{
		return $this->role;
	}

	/**
	 * @return string
	 */
	public function getCountry(): ?string
	{
		return $this->country;
	}

	/**
	 * @return DateTime
	 */
	public function getCreated(): DateTime
	{
		return $this->created;
	}

	/**
	 * @return DateTime
	 */
	public function getEdited(): ?DateTime
	{
		return $this->edited;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		return $this->deleted;
	}

	public function fullName()
	{
		return $this->first . ' ' . $this->last;
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
		$this->$property = $value;
	}

	public function setAccess(array $serials)
	{
		$this->access = $serials;
	}

	public function getAccess(): array
	{
		return $this->access;
	}

	/**
	 * Undocumented function
	 *
	 * @return Collection|Selectable|LocationAccess[]
	 */
	public function getOrganisationAccess()
	{
		return $this->organisationAccess;
	}

	/**
	 * Undocumented function
	 *
	 * @param LocationAccess[] $access
	 * @return void
	 */
	public function setOrganisationAccess(array $access)
	{
		$this->organisationAccess = $access;
	}

	/**
	 * @return LocationAccess 
	 */
	public function getFilteredAccess(): array
	{
		$access = $this->getAccess();
		return from($this->getLocationAccess())
			->where(function (LocationAccess $location) use ($access) {
				return in_array($location->getSerial(), $access);
			})
			->select(function (LocationAccess $location) {
				return $location;
			})
			->toArray();
	}

	public function jsonSerialize()
	{
		$access = [];
		foreach ($this->getLocationAccess() as $locationAccess) {
			$access[] = $locationAccess->jsonSerialize();
		}
		return [
			'uid'            => $this->getUid(),
			'intercom_hash'  => hash_hmac("sha256", $this->getUid(), "dvYyx7KzLSRV0eYAQWVDoi5TrBFyVqMROIrk6uew"),
			'admin'          => $this->getAdmin(),
			'reseller'       => $this->getReseller(),
			'email'          => $this->getEmail(),
			'company'        => $this->getCompany(),
			'first'          => $this->getFirst(),
			'last'           => $this->getLast(),
			'inChargeBee'    => (int)$this->isInChargeBee(),
			'role'           => $this->getRole(),
			'country'        => $this->getCountry(),
			'created'        => $this->getCreated(),
			'edited'         => $this->getEdited(),
			'deleted'        => (int)$this->isDeleted(),
			'full_name' => $this->fullName(),
			'location_access' => $access,
			'organization_access' => $this->getOrganisationAccess()

		];
	}

	/**
	 * @param bool $inChargeBee
	 */
	public function setInChargeBee(bool $inChargeBee): void
	{
		$this->inChargeBee = $inChargeBee;
	}

	/**
	 * @param string $admin
	 * @return OauthUser
	 */
	public function setAdmin(string $admin): OauthUser
	{
		$this->admin = $admin;

		return $this;
	}

	/**
	 * @param string $reseller
	 * @return OauthUser
	 */
	public function setReseller(string $reseller): OauthUser
	{
		$this->reseller = $reseller;

		return $this;
	}

	/**
	 * @param string $email
	 * @return OauthUser
	 */
	public function setEmail(string $email): OauthUser
	{
		$this->email = $email;

		return $this;
	}

	/**
	 * @param string $password
	 * @return OauthUser
	 */
	public function setPassword(string $password): OauthUser
	{
		$this->password = sha1($password);

		return $this;
	}

	/**
	 * @param string $company
	 * @return OauthUser
	 */
	public function setCompany(string $company): OauthUser
	{
		$this->company = $company;

		return $this;
	}

	/**
	 * @param string $first
	 * @return OauthUser
	 */
	public function setFirst(string $first): OauthUser
	{
		$this->first = $first;

		return $this;
	}

	/**
	 * @param string $last
	 * @return OauthUser
	 */
	public function setLast(string $last): OauthUser
	{
		$this->last = $last;

		return $this;
	}

	/**
	 * @param string $stripe_id
	 * @return OauthUser
	 */
	public function setStripeId(string $stripe_id): OauthUser
	{
		$this->stripe_id = $stripe_id;

		return $this;
	}

	/**
	 * @param int $role
	 * @return OauthUser
	 */
	public function setRole(int $role): OauthUser
	{
		$this->role = $role;

		return $this;
	}

	/**
	 * @param string $country
	 * @return OauthUser
	 */
	public function setCountry(string $country): OauthUser
	{
		$this->country = $country;

		return $this;
	}

	/**
	 * @param DateTime $edited
	 * @return OauthUser
	 */
	public function setEdited(DateTime $edited): OauthUser
	{
		$this->edited = $edited;

		return $this;
	}
}
