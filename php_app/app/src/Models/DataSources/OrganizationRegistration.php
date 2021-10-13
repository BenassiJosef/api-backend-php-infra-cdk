<?php


namespace App\Models\DataSources;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use App\Models\Organization;
use App\Models\UserProfile;
use DateTime;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class OrganizationRegistration
 *
 * @ORM\Table(name="organization_registration")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class OrganizationRegistration implements JsonSerializable
{
	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid", nullable=false)
	 * @var UuidInterface $id
	 */
	private $id;

	/**
	 * @ORM\Column(name="organization_id", type="uuid", nullable=false)
	 * @var UuidInterface $organizationId
	 */
	private $organizationId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Organization", cascade={"persist"})
	 * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
	 * @var Organization $organization
	 */
	private $organization;

	/**
	 * @ORM\Column(name="profile_id", type="integer", nullable=false)
	 * @var int $profileId
	 */
	private $profileId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\UserProfile", cascade={"persist"})
	 * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=false)
	 * @var UserProfile $profile
	 */
	private $profile;

	/**
	 * @ORM\OneToMany(targetEntity="RegistrationSource", mappedBy="organizationRegistration", cascade={"persist"})
	 * @var RegistrationSource[] | Collection | Selectable | ArrayCollection
	 */
	private $registrations;

	/**
	 * @ORM\Column(name="last_interacted_at", type="datetime", nullable=false)
	 * @var DateTime $lastInteractedAt
	 */
	private $lastInteractedAt;

	/**
	 * @ORM\Column(name="created_at", type="datetime", nullable=false)
	 * @var DateTime $createdAt
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="data_opt_in_at", type="datetime", nullable=false)
	 * @var DateTime | null $dataOptIn
	 */
	private $dataOptInAt;

	/**
	 * @ORM\Column(name="sms_opt_in_at", type="datetime", nullable=false)
	 * @var DateTime | null $smsOptIn
	 */
	private $smsOptInAt;

	/**
	 * @ORM\Column(name="email_opt_in_at", type="datetime", nullable=false)
	 * @var DateTime | null $emailOptInAt
	 */
	private $emailOptInAt;

	/**
	 * OrganizationRegistration constructor.
	 * @param Organization $organization
	 * @param UserProfile $profile
	 * @param RegistrationSource[] $registrations
	 * @throws Exception
	 */
	public function __construct(
		Organization $organization,
		UserProfile $profile,
		array $registrations = []
	) {
		$this->id               = Uuid::uuid1();
		$this->organizationId   = $organization->getId();
		$this->organization     = $organization;
		$this->profileId        = $profile->getId();
		$this->profile          = $profile;
		$this->registrations    = new ArrayCollection($registrations);
		$this->lastInteractedAt = new DateTime();
		$this->createdAt        = new DateTime();
	}

	/**
	 * @return UuidInterface
	 */
	public function getId(): UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return UuidInterface
	 */
	public function getOrganizationId(): UuidInterface
	{
		return $this->organizationId;
	}

	/**
	 * @return Organization
	 */
	public function getOrganization(): Organization
	{
		return $this->organization;
	}


	/**
	 * @return int
	 */
	public function getProfileId(): int
	{
		return $this->profileId;
	}

	/**
	 * @return bool
	 */
	public function getDataOptIn(): bool
	{
		if ($this->dataOptInAt) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function getSmsOptIn(): bool
	{
		if ($this->smsOptInAt) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function getEmailOptIn(): bool
	{
		if ($this->emailOptInAt) {
			return true;
		}
		return false;
	}


	public function setDataOptIn(bool $optIn)
	{
		if ($optIn) {
			$this->dataOptInAt = new DateTime();
		} else {
			$this->dataOptInAt = null;
			$this->setEmailOptIn(false);
			$this->setSmsOptIn(false);
		}
	}


	public function setSmsOptIn(bool $optIn)
	{
		if ($optIn) {
			$this->smsOptInAt = new DateTime();
		} else {
			$this->smsOptInAt = null;
		}
	}


	public function setEmailOptIn(bool $optIn)
	{
		if ($optIn) {
			$this->emailOptInAt = new DateTime();
		} else {
			$this->emailOptInAt = null;
		}
	}

	/**
	 * @param UserProfile $profile
	 * @return OrganizationRegistration
	 */
	public function setProfile(UserProfile $profile): OrganizationRegistration
	{
		$this->profile   = $profile;
		$this->profileId = $profile->getId();
		return $this;
	}

	/**
	 * @return UserProfile
	 */
	public function getProfile(): UserProfile
	{
		return $this->profile;
	}

	public function trackRegistrationSource(
		DataSource $dataSource,
		string $serial,
		int $interactions = 1
	) {
		$criteria = Criteria::create()
			->where(Criteria::expr()->eq('dataSourceId', $dataSource->getId()))
			->andWhere(Criteria::expr()->eq('serial', $serial));

		$registrations = $this->registrations->matching($criteria);
		$registration  = new RegistrationSource($this, $dataSource, $serial);
		if (count($registrations) !== 0) {
			$registration = $registrations[0];
		}
		$registration->addInteractions($interactions);
		if (count($registrations) === 0) {
			$this->registrations->add($registration);
		}
	}

	/**
	 * @return RegistrationSource[]|ArrayCollection|Collection|Selectable
	 */
	public function getRegistrations()
	{
		return $this->registrations;
	}

	/**
	 * @return DateTime
	 */
	public function getLastInteractedAt(): DateTime
	{
		return $this->lastInteractedAt;
	}

	/**
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize()
	{
		return [
			'id'                 => $this->getId()->toString(),
			'profileId'          => $this->getProfileId(),
			'profile'            => $this->getProfile()->jsonSerialize(),
			'registrations'      => $this->getRegistrations()->toArray(),
			'created_at'         => $this->getCreatedAt(),
			'last_interacted_at' => $this->getLastInteractedAt(),
			'organization_name' => $this->getOrganization()->getName(),
			'organization_id' => $this->getOrganization()->getId(),
			'data_opt_in' => $this->getDataOptIn(),
			'sms_opt_in' => $this->getSmsOptIn(),
			'email_opt_in' => $this->getEmailOptIn()
		];
	}
}
