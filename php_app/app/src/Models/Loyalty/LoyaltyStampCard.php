<?php


namespace App\Models\Loyalty;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NegativeStampException;
use App\Models\Loyalty\Exceptions\NotActiveException;
use App\Models\Loyalty\Exceptions\NotEnoughStampsException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\FlushingNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\StampCard\StorageStampCard;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Loyalty\StampScheme\Redeemable;
use App\Package\Loyalty\StampScheme\Stampable;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LoyaltyReward
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_stamp_card")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class LoyaltyStampCard implements JsonSerializable, StorageStampCard, Redeemable, Stampable
{
	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid")
	 * @var UuidInterface $id
	 */
	private $id;

	/**
	 * @ORM\Column(name="scheme_id", type="uuid", nullable=false)
	 * @var UuidInterface $schemeId
	 */
	private $schemeId;

	/**
	 * @ORM\ManyToOne(targetEntity="LoyaltyStampScheme", cascade={"persist"})
	 * @ORM\JoinColumn(name="scheme_id", referencedColumnName="id", nullable=false)
	 * @var LoyaltyStampScheme $scheme
	 */
	private $scheme;

	/**
	 * @ORM\Column(name="profile_id", type="integer")
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
	 * @ORM\Column(name="collected_stamps", type="integer", nullable=false)
	 * @var int $collectedStamps
	 */
	private $collectedStamps;

	/**
	 * @ORM\OneToMany(targetEntity="App\Models\Loyalty\LoyaltyStampCardEvent", mappedBy="card", cascade="persist")
	 * @var Collection | Selectable | LoyaltyStampCardEvent[] $events
	 */
	private $events;

	/**
	 * @ORM\Column(name="created_at", type="datetime", nullable=false)
	 * @var DateTime $createdAt
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="activated_at", type="datetime", nullable=true)
	 * @var DateTime | null
	 */
	private $activatedAt;

	/**
	 * @ORM\Column(name="last_stamped_at", type="datetime", nullable=true)
	 * @var DateTime | null
	 */
	private $lastStampedAt;

	/**
	 * @ORM\Column(name="redeemed_at", type="datetime", nullable=true)
	 * @var DateTime | null
	 */
	private $redeemedAt;

	/**
	 * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
	 * @var DateTime | null
	 */
	private $deletedAt;

	/**
	 * @var FlushingNotifier $eventNotifier
	 */
	private $eventNotifier;

	/**
	 * @ORM\Column(name="organization_registration_id", type="uuid")
	 * @var UuidInterface $organizationRegistrationId
	 */
	private $organizationRegistrationId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\DataSources\OrganizationRegistration", cascade={"persist"})
	 * @ORM\JoinColumn(name="organization_registration_id", referencedColumnName="id", nullable=false)
	 * @var OrganizationRegistration $organizationRegistration
	 */
	private $organizationRegistration;

	/**
	 * LoyaltyStampCard constructor.
	 * @param LoyaltyStampScheme $scheme
	 * @param OrganizationRegistration $registration
	 * @param int $collectedStamps
	 * @param EventNotifier|null $eventNotifier
	 * @throws AlreadyRedeemedException
	 * @throws FullCardException
	 * @throws NegativeStampException
	 * @throws OverstampedCardException
	 * @throws StampedTooRecentlyException
	 */
	public function __construct(
		LoyaltyStampScheme $scheme,
		OrganizationRegistration $registration,
		int $collectedStamps = 0,
		?EventNotifier $eventNotifier = null
	) {
		if ($eventNotifier === null) {
			$eventNotifier = new NopNotifier();
		}
		$this->eventNotifier = new FlushingNotifier($eventNotifier);
		$this->id            = Uuid::uuid4();
		$this->schemeId      = $scheme->getId();
		$this->scheme        = $scheme;
		$this->profileId     = $registration->getProfile()->getId();
		$this->profile       = $registration->getProfile();
		$this->organizationRegistration = $registration;
		$this->organizationRegistrationId = $registration->getId();
		$this->events        = new ArrayCollection();
		$this->addEvent(
			LoyaltyStampCardEvent::newCreateEvent($this)
		);
		$this->collectedStamps = 0;
		$this->stamp(StampContext::emptyContext(), $collectedStamps);
		$this->createdAt = new DateTime();
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
	public function getSchemeId(): UuidInterface
	{
		return $this->schemeId;
	}

	/**
	 * @return LoyaltyStampScheme
	 */
	public function getScheme(): LoyaltyStampScheme
	{
		return $this->scheme;
	}

	/**
	 * @return int
	 */
	public function getProfileId(): int
	{
		return $this->profileId;
	}

	/**
	 * @return UserProfile
	 */
	public function getProfile(): UserProfile
	{
		return $this->profile;
	}

	/**
	 * @param EventNotifier $eventNotifier
	 * @return LoyaltyStampCard
	 */
	public function setEventNotifier(EventNotifier $eventNotifier): LoyaltyStampCard
	{
		if ($this->eventNotifier === null) {
			$this->eventNotifier = new FlushingNotifier(
				new NopNotifier()
			);
		}
		$this->eventNotifier->setBase($eventNotifier);
		return $this;
	}

	/**
	 * @return LoyaltyStampCardEvent[]
	 */
	public function getEvents(): array
	{
		return from($this->events)
			->select(
				function (LoyaltyStampCardEvent $event): LoyaltyStampCardEvent {
					return $event;
				},
				function (LoyaltyStampCardEvent $event): string {
					return $event->getId()->toString();
				}
			)
			->toArray();
	}

	/**
	 * @param LoyaltyStampCardEvent ...$events
	 */
	private function addEvent(LoyaltyStampCardEvent ...$events): void
	{
		$this->eventNotifier->notify(...$events);
		foreach ($events as $event) {
			$this->events->add($event);
		}
	}

	/**
	 * @return int
	 */
	public function getCollectedStamps(): int
	{
		return $this->collectedStamps;
	}

	/**
	 * @return int
	 */
	public function getRemainingStamps(): int
	{
		return $this->scheme->getRequiredStamps() - $this->collectedStamps;
	}

	/**
	 * @return bool
	 */
	public function isFull(): bool
	{
		$collectedStamps = $this->collectedStamps;
		$requiredStamps  = $this->scheme->getRequiredStamps();
		return $collectedStamps === $requiredStamps;
	}

	/**
	 * @return DateTime|null
	 */
	private function nextStampTime(): ?DateTime
	{
		if ($this->lastStampedAt === null) {
			return null;
		}
		$lastStampTimestamp = $this
			->lastStampedAt
			->getTimestamp();
		$cooldownDuration   = $this
			->scheme
			->getStampCooldownDuration();
		if ($cooldownDuration === null || $cooldownDuration === 0) {
			return null;
		}
		$nextStampTimestamp = $lastStampTimestamp + $cooldownDuration;
		$nextStampTime      = new DateTime();
		$nextStampTime->setTimestamp($nextStampTimestamp);
		return $nextStampTime;
	}

	public function canStampAtThisTime(): bool
	{
		$nextStampTime = $this->nextStampTime();
		if ($nextStampTime === null) {
			return true;
		}
		$now = new DateTime();
		return $now >= $nextStampTime;
	}

	/**
	 * @param int $stamps
	 * @param StampContext $context
	 * @throws AlreadyActivatedException
	 * @throws AlreadyRedeemedException
	 * @throws FullCardException
	 * @throws NegativeStampException
	 * @throws OverstampedCardException
	 * @throws StampedTooRecentlyException
	 */
	public function stamp(
		StampContext $context,
		int $stamps = 1
	): void {
		$stamper = $context->getStamper();
		if ($stamps === 0) {
			return;
		}
		if ($stamps < 0) {
			throw new NegativeStampException($this);
		}
		if (!$this->isActivated()) {
			$this->activate($stamper);
		}
		if ($this->isRedeemed()) {
			throw new AlreadyRedeemedException($this);
		}
		if ($this->isFull()) {
			throw new FullCardException($this);
		}
		if ($stamper === null && !$this->canStampAtThisTime()) {
			throw new StampedTooRecentlyException($this);
		}
		$collectedStamps = $this->collectedStamps;
		$requiredStamps  = $this->scheme->getRequiredStamps();
		if ($collectedStamps + $stamps > $requiredStamps) {
			throw new OverstampedCardException($this);
		}
		$stampEvent            = LoyaltyStampCardEvent::newStampEvent(
			$this,
			$stamps,
			$context
		);
		$this->collectedStamps += $stamps;
		$this->addEvent($stampEvent);
		$this->lastStampedAt = $stampEvent->getCreatedAt();
		if ($this->collectedStamps === $requiredStamps) {
			$this->addEvent(
				LoyaltyStampCardEvent::newFilledEvent($this)
			);
		}
	}

	/**
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @return DateTime|null
	 */
	public function getLastStampedAt(): ?DateTime
	{
		return $this->lastStampedAt;
	}

	/**
	 * @return DateTime|null
	 */
	public function getActivatedAt(): ?DateTime
	{
		return $this->activatedAt;
	}

	/**
	 * @return bool
	 */
	public function isActivated(): bool
	{
		return $this->activatedAt !== null;
	}

	/**
	 * @param OauthUser|null $activator
	 * @throws AlreadyActivatedException
	 * @throws Exception
	 */
	public function activate(OauthUser $activator = null)
	{
		if ($this->activatedAt !== null) {
			throw new AlreadyActivatedException($this);
		}
		$activationEvent = LoyaltyStampCardEvent::newActivateEvent($this, $activator);
		$this->addEvent($activationEvent);
		$this->activatedAt = $activationEvent->getCreatedAt();
	}

	/**
	 * @return DateTime|null
	 */
	public function getRedeemedAt(): ?DateTime
	{
		return $this->redeemedAt;
	}

	/**
	 * @return bool
	 */
	public function isRedeemed(): bool
	{
		return $this->redeemedAt !== null;
	}

	/**
	 * @param OauthUser|null $redeemer
	 * @return LoyaltyReward
	 * @throws NotActiveException
	 * @throws NotEnoughStampsException
	 * @throws AlreadyRedeemedException
	 * @throws Exception
	 */
	public function redeem(OauthUser $redeemer = null): LoyaltyReward
	{
		if (!$this->isActivated()) {
			throw new NotActiveException($this);
		}
		if ($this->isRedeemed()) {
			throw new AlreadyRedeemedException($this);
		}
		if (!$this->isFull()) {
			throw new NotEnoughStampsException($this);
		}
		$redemptionEvent = LoyaltyStampCardEvent::newRedemptionEvent($this, $redeemer);
		$this->addEvent($redemptionEvent);
		$this->redeemedAt = $redemptionEvent->getCreatedAt();
		return $this->scheme->getReward();
	}

	/**
	 * @ORM\PostPersist
	 * @ORM\PostUpdate
	 */
	public function flush(): void
	{
		$this->eventNotifier->flush();
	}

	/**
	 * @return DateTime|null
	 */
	public function getDeletedAt(): ?DateTime
	{
		return $this->deletedAt;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		return $this->deletedAt !== true;
	}

	/**
	 * @param OauthUser|null $deleter
	 * @throws Exception
	 */
	public function delete(?OauthUser $deleter = null): void
	{
		$event = LoyaltyStampCardEvent::newDeleteEvent($this, $deleter);
		$this->addEvent($event);
		$this->deletedAt = $event->getCreatedAt();
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize()
	{
		return [
			'id'                 => $this->getId()->toString(),
			'schemeId'           => $this->getSchemeId()->toString(),
			'profileId'          => $this->profileId,
			'collectedStamps'    => $this->collectedStamps,
			'remainingStamps'    => $this->getRemainingStamps(),
			'isFull'             => $this->isFull(),
			'events'             => $this->getEvents(),
			'createdAt'          => $this->createdAt,
			'lastStampedAt'      => $this->lastStampedAt,
			'canStampAtThisTime' => $this->canStampAtThisTime(),
			'activatedAt'        => $this->activatedAt,
			'isActivated'        => $this->isActivated(),
		];
	}
}
