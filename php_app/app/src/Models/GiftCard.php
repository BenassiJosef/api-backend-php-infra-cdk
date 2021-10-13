<?php


namespace App\Models;

use App\Models\Billing\Organisation\Subscriptions;
use App\Models\DataSources\OrganizationRegistration;
use App\Package\GiftCard\Exceptions\AlreadyRedeemedException;
use App\Package\GiftCard\Exceptions\AlreadyRefundedException;
use App\Package\GiftCard\GiftCardEvent;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Endroid\QrCode\QrCode;
use Exception;
use NumberFormatter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JsonSerializable;

/**
 * Class GiftCard
 *
 * @ORM\Table(name="gift_card")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @package App\Models
 */
class GiftCard implements JsonSerializable
{
	const STATUS_ACTIVE = 'active';

	const STATUS_REDEEMED = 'redeemed';

	const STATUS_REFUNDED = 'refunded';

	const STATUS_UNPAID = 'unpaid';


	/**
	 * @return string[]
	 */
	public static function availableStatuses(): array
	{
		return [
			self::STATUS_ACTIVE,
			self::STATUS_REDEEMED,
			self::STATUS_REFUNDED,
			self::STATUS_UNPAID
		];
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid")
	 * @var UuidInterface $id
	 */
	private $id;

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
	 * @ORM\Column(name="gift_card_settings_id", type="uuid")
	 * @var UuidInterface $giftCardSettingsId
	 */
	private $giftCardSettingsId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\GiftCardSettings", cascade={"persist"})
	 * @ORM\JoinColumn(name="gift_card_settings_id", referencedColumnName="id", nullable=false)
	 * @var GiftCardSettings $giftCardSettings
	 */
	private $giftCardSettings;

	/**
	 * @ORM\Column(name="organization_id", type="uuid")
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
	 * @ORM\Column(name="transaction_id", type="string")
	 * @var string $transactionId
	 */
	private $transactionId;

	/**
	 * @ORM\Column(name="amount", type="integer")
	 * @var int $amount
	 */
	private $amount;

	/**
	 * @ORM\Column(name="currency", type="string")
	 * @var string $currency
	 */
	private $currency;

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
	 * @ORM\Column(name="created_at", type="datetime", nullable=false)
	 * @var DateTime $createdAt
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="activated_at", type="datetime", nullable=true)
	 * @var DateTime | null $activatedAt
	 */
	private $activatedAt;

	/**
	 * @ORM\Column(name="redeemed_by", type="string", nullable=true)
	 * @var string | null $redeemedBy
	 */
	private $redeemedBy;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
	 * @ORM\JoinColumn(name="redeemed_by", referencedColumnName="uid", nullable=true)
	 * @var OauthUser | null $redeemedByUser
	 */
	private $redeemedByUser;

	/**
	 * @ORM\Column(name="redeemed_at", type="datetime", nullable=true)
	 * @var DateTime | null $redeemedAt
	 */
	private $redeemedAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
	 * @ORM\JoinColumn(name="refunded_by", referencedColumnName="uid", nullable=true)
	 * @var OauthUser | null $refundedByUser
	 */
	private $refundedByUser;

	/**
	 * @ORM\Column(name="refunded_by", type="string", nullable=true)
	 * @var string $refundedBy
	 */
	private $refundedBy;

	/**
	 * @ORM\Column(name="refunded_at", type="datetime", nullable=true)
	 * @var DateTime | null $redeemedAt
	 */
	private $refundedAt;

	/**
	 * @var IDPrettyfier $idPrettyfier
	 */
	private $idPrettyfier;

	/**
	 * @var GiftCardEvent[] $events
	 */
	private $events = [];

	/**
	 * GiftCard constructor.
	 * @param GiftCardSettings $giftCardSettings
	 * @param OrganizationRegistration $profile
	 * @param int $amount
	 * @param string $currency
	 * @throws Exception
	 */
	public function __construct(
		GiftCardSettings $giftCardSettings,
		OrganizationRegistration $registration,
		int $amount,
		string $currency = "GBP"
	) {
		$this->id                 = Uuid::uuid4();
		$this->giftCardSettingsId = $giftCardSettings->getId();
		$this->giftCardSettings   = $giftCardSettings;
		$this->organization       = $giftCardSettings->getOrganization();
		$this->organizationId     = $giftCardSettings->getOrganization()->getId();
		$this->profile            = $registration->getProfile();
		$this->profileId          = $registration->getProfile()->getId();
		$this->organizationRegistration = $registration;
		$this->organizationRegistrationId = $registration->getId();
		$this->amount             = $amount;
		$this->currency           = $currency;
		$this->createdAt          = new DateTime();

		$this->idPrettyfier = new HumanReadable();
		$this->logEvent(GiftCardEvent::created($this));
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
	public function getGiftCardSettingsId(): UuidInterface
	{
		return $this->giftCardSettingsId;
	}

	/**
	 * @return GiftCardSettings
	 */
	public function getGiftCardSettings(): GiftCardSettings
	{
		return $this->giftCardSettings;
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
	 * @return string
	 */
	public function getTransactionId(): ?string
	{
		return $this->transactionId;
	}

	/**
	 * @return int
	 */
	public function getAmount(): int
	{
		return $this->amount;
	}

	/**
	 * @return string
	 */
	public function getCurrency(): string
	{
		return $this->currency;
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
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @return DateTime|null
	 */
	public function getActivatedAt(): ?DateTime
	{
		return $this->activatedAt;
	}

	/**
	 * @return DateTime|null
	 */
	public function getRedeemedAt(): ?DateTime
	{
		return $this->redeemedAt;
	}

	/**
	 * @return OauthUser|null
	 */
	public function getRedeemedBy(): ?OauthUser
	{
		return $this->redeemedByUser;
	}

	/**
	 * @return string | null
	 */
	public function getRedeemedById(): ?string
	{
		return $this->redeemedBy;
	}

	/**
	 * @return OauthUser|null
	 */
	public function getRefundedByUser(): ?OauthUser
	{
		return $this->refundedByUser;
	}

	/**
	 * @return string | null
	 */
	public function getRefundedBy(): ?string
	{
		return $this->refundedBy;
	}

	/**
	 * @return DateTime|null
	 */
	public function getRefundedAt(): ?DateTime
	{
		return $this->refundedAt;
	}

	/**
	 * @return string
	 */
	public function humanID(): string
	{
		if ($this->idPrettyfier === null) {
			$this->idPrettyfier = new HumanReadable();
		}

		return $this->idPrettyfier->prettify($this->getId());
	}

	/**
	 * @param UserProfile $profile
	 * @return $this
	 */
	public function changeOwner(UserProfile $profile): self
	{
		$this->logEvent(
			GiftCardEvent::ownershipChanged(
				$this,
				$this->profile,
				$profile
			)
		);
		$this->profile   = $profile;
		$this->profileId = $profile->getId();
		return $this;
	}

	/**
	 * @param OauthUser $user
	 * @throws AlreadyRedeemedException
	 */
	public function redeem(OauthUser $user)
	{
		if ($this->redeemedAt !== null) {
			throw new AlreadyRedeemedException($this->id->toString());
		}
		$this->redeemedBy     = $user->getUid();
		$this->redeemedByUser = $user;
		$this->redeemedAt     = new DateTime();
		$this->logEvent(GiftCardEvent::redeemed($this));
	}

	/**
	 * @param OauthUser $user
	 * @throws AlreadyRefundedException
	 */
	public function refund(OauthUser $user)
	{
		if ($this->refundedAt !== null) {
			throw new AlreadyRefundedException($this->id->toString());
		}
		$this->refundedAt     = new DateTime();
		$this->refundedBy     = $user->getUid();
		$this->refundedByUser = $user;
		$this->logEvent(GiftCardEvent::refunded($this));
	}

	/**
	 * @param string $transactionId
	 * @throws Exception
	 */
	public function activate(string $transactionId)
	{
		$this->transactionId = $transactionId;
		$this->activatedAt   = new DateTime();
		$this->logEvent(GiftCardEvent::activated($this));
	}

	/**
	 * @return string
	 */
	public function description(): string
	{
		return "Your Gift Card for {$this->formattedCurrency()} at {$this->getOrganization()->getName()}";
	}

	/**
	 * @return string
	 */
	public function status(): string
	{
		if ($this->getRefundedAt() !== null) {
			return self::STATUS_REFUNDED;
		}
		if ($this->getActivatedAt() !== null && $this->getRedeemedAt() === null) {
			return self::STATUS_ACTIVE;
		}
		if ($this->getRedeemedAt() !== null) {
			return self::STATUS_REDEEMED;
		}
		return self::STATUS_UNPAID;
	}

	/**
	 * @return string
	 */
	public function formattedCurrency(): string
	{
		$formatter = NumberFormatter::create('en_GB', NumberFormatter::CURRENCY);

		return $formatter->formatCurrency($this->getAmount() / 100, $this->getCurrency());
	}

	/**
	 * @return float
	 */
	public function feePercentage(): float
	{
		$stripeFee    = 0.014;
		$paidFee      = 0.02;
		$freeFee      = 0.06;
		$fee          = $freeFee - $stripeFee;
		$subscription = $this
			->organization
			->getSubscription();
		$hasGiftCards = true;
		if ($subscription !== null) {
			$hasGiftCards = $subscription->hasAddon(Subscriptions::ADDON_GIFT_CARDS);
		}
		if ($hasGiftCards) {
			$fee = $paidFee - $stripeFee;
		}
		return $fee;
	}

	/**
	 * @return int
	 */
	public function fee(): int
	{
		return ceil($this->getAmount() * $this->feePercentage());
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return [
			"id"               => $this->getId(),
			"prettyId"         => $this->humanID(),
			"giftCardSettings" => $this->getGiftCardSettings(),
			"transactionId"    => $this->getTransactionId(),
			"value"            => [
				"amount"    => $this->getAmount(),
				"currency"  => $this->getCurrency(),
				"formatted" => $this->formattedCurrency(),
			],
			"profileId"        => $this->getProfileId(),
			"createdAt"        => $this->getCreatedAt(),
			"activatedAt"      => $this->getActivatedAt(),
			"redeemedAt"       => $this->getRedeemedAt(),
			"redeemedBy"       => $this->getRedeemedBy(),
			"status"           => $this->status(),
			"profile"          => $this->getProfile(),
			"refundedAt"       => $this->getRefundedAt(),
			"refundedBy"       => $this->getRefundedByUser(),
		];
	}


	/**
	 * @param string $token
	 * @return array
	 */
	public function stripeDetails(string $token): array
	{
		return [
			'amount'          => $this->getAmount(),
			'application_fee' => $this->fee(),
			'currency'        => $this->getCurrency(),
			'source'          => $token,
			'description'     => $this->description(),
		];
	}

	/**
	 * @return array[]
	 */
	public function emailSendTo(): array
	{
		return [
			[
				'to'   => $this->getProfile()->getEmail(),
				'name' => $this->getProfile()->getFullName(),
			]
		];
	}

	/**
	 * @return array
	 */
	public function emailDetails(): array
	{
		return [
			'image'   => $this->getGiftCardSettings()->getImage(),
			'email'   => $this->getProfile()->getEmail(),
			'first'   => $this->getProfile()->getFirst(),
			'last'    => $this->getProfile()->getLast(),
			'qr'      => $this->qrCodeURI(),
			'amount'  => $this->formattedCurrency(),
			'colour'  => $this->getGiftCardSettings()->getColour(),
			'humanId' => $this->humanID(),
		];
	}

	public function qrCodeURI(): string
	{
		$qrCode = new QrCode($this->humanID());
		$qrCode->setSize(344);

		return $qrCode->writeDataUri();
	}

	/**
	 * @ORM\PostPersist
	 * @ORM\PostUpdate
	 */
	public function flushEvents(): void
	{
		if (!extension_loaded('newrelic')) {
			$this->events = [];
			return;
		}
		foreach ($this->events as $event) {
			newrelic_record_custom_event('GiftCardEvent', $event->jsonSerialize());
		}
		$this->events = [];
	}

	private function logEvent(GiftCardEvent $event): void
	{
		$this->events[] = $event;
	}
}
