<?php

namespace App\Models;

use App\Models\Billing\Organisation\Subscriptions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Selectable;
use InvalidArgumentException;
use Nette\Neon\Entity;
use phpDocumentor\Reflection\Types\Boolean;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use DateTime;
use Exception;
use App\Models\Locations\LocationSettings;
use JsonSerializable;

/**
 * Class Organization
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity
 * @package App\Models
 */
class Organization implements JsonSerializable
{
	/**
	 * @var string
	 */
	const RootType = 'root';

	/**
	 * @var string
	 */
	const ResellerType = 'reseller';

	/**
	 * @var string
	 */
	const DefaultType = 'default';

	/**
	 * @var string[] $allTypes
	 */
	public static $allTypes = [
		self::RootType,
		self::ResellerType,
		self::DefaultType
	];

	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid")
	 * @var UuidInterface $id
	 */
	private $id;

	/**
	 * @ORM\Column(name="parent_organization_id", type="uuid", nullable=true)
	 * @var UuidInterface $parentId
	 */
	private $parentId;

	/**
	 * @ORM\Column(name="owner_id", type="uuid", nullable=false)
	 * @var UuidInterface $ownerId
	 */
	private $ownerId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
	 * @ORM\JoinColumn(name="owner_id", referencedColumnName="uid", nullable=false)
	 * @var OauthUser $owner
	 */
	private $owner;

	/**
	 * @ORM\Column(name="chargebee_customer_id", type="string")
	 * @var string $chargebeeCustomerId
	 */
	private $chargebeeCustomerId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Organization", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_organization_id", referencedColumnName="id", nullable=true)
	 * @var Organization | null $parent
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="Organization", mappedBy="parent")
	 * @var Collection | Selectable | Organization[] $children
	 */
	private $children;

	/**
	 * @ORM\OneToOne(targetEntity="App\Models\Billing\Organisation\Subscriptions", mappedBy="organization")
	 * @var Subscriptions $subscription
	 */
	private $subscription;

	/**
	 * @ORM\Column(name="name", type="string")
	 * @var string $name
	 */
	private $name;

	/**
	 * @ORM\Column(name="type", type="string")
	 * @var string $type
	 */
	private $type = self::DefaultType;

	/**
	 * @ORM\Column(name="created_at", type="datetime", nullable=false)
	 * @var DateTime $createdAt
	 */
	private $createdAt;

	/**
	 * @ORM\OneToMany(targetEntity="App\Models\Locations\LocationSettings", mappedBy="organization", cascade="persist")
	 * @var Collection | Selectable | NetworkSettings[] $locations
	 */
	private $locations;

	/**
	 * @var Collection | Selectable | LocationSettings[] $filteredLocations
	 */
	private $filteredLocations;

	/**
	 * @var boolean $isRestrictedByLocation
	 */
	private $isRestrictedByLocation = false;

	/**
	 * Organization constructor.
	 * @param string $name
	 * @param OauthUser $owner
	 * @param Organization|null $parent
	 * @param string|null $chargebeeCustomerId
	 * @throws Exception
	 */
	public function __construct(string $name, OauthUser $owner, ?Organization $parent = null, ?string $chargebeeCustomerId = null)
	{
		$this->id   = Uuid::uuid1();
		$this->name = $name;
		if ($parent !== null) {
			$this->parentId = $parent->getId();
		}
		$this->parent              = $parent;
		$this->owner               = $owner;
		$this->chargebeeCustomerId = $chargebeeCustomerId;
		$this->ownerId             = Uuid::fromString($owner->getUid());
		$this->children            = new ArrayCollection();
		$this->locations           = new ArrayCollection();
		$this->createdAt           = new DateTime();
	}

	/**
	 * @param Organization $owner
	 * @return bool
	 */
	public function belongsTo(Organization $owner): bool
	{
		if ($this->getId()->equals($owner->getId())) {
			return true;
		}
		$parent = $this->getParent();
		while ($parent !== null) {
			if ($parent->getId()->equals($owner->getId())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return OauthUser
	 */
	public function getOwner(): OauthUser
	{
		return $this->owner;
	}

	/**
	 * @param OauthUser $user User to test access for
	 * @param Role ...$roles Pass an empty array for any role
	 * @return bool True if the user has access
	 */
	public function hasAccess(OauthUser $user, Role ...$roles): bool
	{
		// if the user is the owner then they can do anything so role is not important
		if ($this->ownerId == $user->getUid()) {
			return true;
		}
		// check that the user is in the specified role
		$roleIds = [];
		foreach ($roles as $role) {
			$roleIds[] = $role->getId();
		}
		$criteria = Criteria::create()
			->where(
				new Comparison('userId', Comparison::EQ, $user->getUid())
			);
		if (count($roleIds) > 0) {
			$criteria = $criteria->andWhere(
				new Comparison('roleId', Comparison::IN, $roleIds)
			);
		}

		$matches = $this->access->matching($criteria);
		if (count($matches) !== 1) {
			return false;
		}

		return true;
	}

	/**
	 * @return UuidInterface
	 */
	public function getId(): UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return Subscriptions
	 */
	public function getSubscription(): ?Subscriptions
	{
		return $this->subscription;
	}


	public function getParentOrganizationId(): ?UuidInterface
	{
		return $this->parentId;
	}

	/**
	 * @return Organization|null
	 */
	public function getParent(): ?Organization
	{
		return $this->parent;
	}

	/**
	 * @return Collection | Organization[] | array
	 */
	public function getChildren(): Collection
	{
		return $this->children;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName(string $name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return LocationSettings[]|Collection|Selectable
	 */
	public function getLocations()
	{
		return $this->locations;
	}

	/**
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	public function getIsRestrictedByLocation(): bool
	{
		return $this->isRestrictedByLocation;
	}

	public function getFilteredLocations(array $serials)
	{

		$locations = $this->getLocations();

		$this->filteredLocations = array_values(
			from($locations)
				->where(function (LocationSettings $location) use ($serials) {
					return in_array($location->getSerial(), $serials);
				})
				->toArray()
		);

		$locationsCount = count($this->filteredLocations);

		$this->isRestrictedByLocation = $locationsCount !== count($locations);
		return $this;
	}

	/**
	 * @return LocationSettings|Collection|Selectable
	 */
	public function getAccessableLocations()
	{
		if (!$this->filteredLocations) return [];
		return from($this->filteredLocations);
	}

	/**
	 * @return array
	 */

	public function getAccessableSerials()
	{
		if (!$this->filteredLocations) return [];
		return array_values(
			from($this->filteredLocations)
				->select(function (LocationSettings $location): string {
					return $location->getSerial();
				})
				->toArray()
		);
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerialize()
	{
		$parent   = $this->parent;
		$parentId = null;
		if ($parent !== null) {
			$parentId = $parent->getId();
		}
		$locations = [];
		if (!$this->filteredLocations) {
			foreach ($this->getLocations() as $location) {
				$locations[] = $location->jsonSerialize();
			}
		} else {
			foreach ($this->filteredLocations as $location) {
				$locations[] = $location->jsonSerialize();
			}
		}


		return [
			"id"                    => $this->id,
			"name"                  => $this->getName(),
			"ownerId"               => $this->ownerId,
			"parentId"              => $parentId,
			"locations"             => $locations,
			"type"                  => $this->getType(),
			"chargebee_customer_id" => $this->getChargebeeCustomerId(),
			"is_restricted_by_location" => $this->isRestrictedByLocation,
			'serials' => $this->getAccessableSerials()
		];
	}

	/**
	 * @param Organization $child Child to add
	 */
	public function addChild(Organization $child)
	{
		$this->children->add($child);
		$child->setParent($this);
	}

	/**
	 * @param Organization $child Child to remove
	 */
	public function removeChild(Organization $child)
	{
		$child->setParent(null);
		$this->children->remove($child);
	}

	/**
	 * @param Organization[] $children
	 */
	public function setChildren(array $children)
	{
		$this->children->clear();
		foreach ($children as $child) {
			$child->setParent($this);
			$this->children->add($child);
		}
	}

	/**
	 * @param string $type
	 * @return Organization
	 */
	public function setType(string $type): Organization
	{
		$invalidType = $type !== self::DefaultType &&
			$type !== self::RootType &&
			$type !== self::ResellerType;
		if ($invalidType) {
			throw new InvalidArgumentException("($type) is not a valid organization type");
		}
		$this->type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param Organization $newParent The new parent
	 * @return Organization
	 */
	public function setParent(Organization $newParent): Organization
	{
		$this->parentId = $newParent->getId();
		$this->parent   = $newParent;

		return $this;
	}

	/**
	 * @return UuidInterface
	 */
	public function getOwnerId(): UuidInterface
	{
		return $this->ownerId;
	}

	/**
	 * @return string
	 */
	public function getChargebeeCustomerId(): ?string
	{
		return $this->chargebeeCustomerId;
	}

	/**
	 * @param string $chargebeeCustomerId
	 * @return $this
	 */
	public function setChargebeeCustomerId(string $chargebeeCustomerId)
	{
		$this->chargebeeCustomerId = $chargebeeCustomerId;

		return $this;
	}

	/**
	 * @param LocationSettings $location Location to add
	 */
	public function addLocation(LocationSettings $location)
	{
		$this->locations->add($location);
		$location->setOrganization($this);
	}
}
