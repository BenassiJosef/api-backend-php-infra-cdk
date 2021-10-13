<?php

namespace App\Models\Reviews;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\UserProfile;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JsonSerializable;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * UserReview
 *
 * @ORM\Table(name="user_review")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserReview implements JsonSerializable
{

	/**
	 * UserReview constructor.
	 * @param ReviewSettings $settings
	 * @param string $review
	 * @param int $rating
	 * @param array $metadata
	 * @param OrganizationRegistration $profile
	 * @throws Exception
	 */

	public function __construct(
		ReviewSettings $settings,
		string $review,
		int $rating,
		string $platform,
		?array $metadata,
		?OrganizationRegistration $registration
	) {
		$this->id =    Uuid::uuid4();
		$this->createdAt = new DateTime();
		$this->reviewSettingsId = $settings->getId();
		$this->reviewSettings = $settings;
		$this->organization = $settings->getOrganization();
		$this->organizationId = $settings->getOrganization()->getId();
		$this->organizationRegistration  = $registration;

		$this->review = $review;
		$this->rating = $rating;
		$this->metadata = $metadata ?? [];
		$this->platform = $platform;
		$this->keywords =      new ArrayCollection();
		if (!is_null($registration)) {
			$this->organizationRegistrationId  = $registration->getId();
			$this->profile = $registration->getProfile();
			$this->profileId = $registration->getProfile()->getId();
		}
	}

	public function setSentimentFromAwsComprehend(array $data)
	{
		$score = $data['SentimentScore'];
		$this->scoreNegative  = $score['Negative'];
		$this->scoreMixed = $score['Mixed'];
		$this->scorePositive = $score['Positive'];
		$this->scoreNeutral = $score['Neutral'];
		$this->sentiment = $data['Sentiment'];
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
	 * @ORM\Column(name="review_settings_id", type="uuid", nullable=false)
	 * @var UuidInterface $reviewSettingsId
	 */
	private $reviewSettingsId;

	/**
	 * @ORM\ManyToOne(targetEntity="ReviewSettings", cascade={"persist"})
	 * @ORM\JoinColumn(name="review_settings_id", referencedColumnName="id", nullable=false)
	 * @var ReviewSettings $reviewSettings
	 */
	private $reviewSettings;

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
	 * @ORM\OneToMany(targetEntity="UserReviewKeywords", mappedBy="userReview", cascade="persist")
	 * @var Collection | Selectable | UserReviewKeywords[] $keywords
	 */
	private $keywords;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="created_at", type="datetime")
	 */
	private $createdAt;

	/**
	 * @var string
	 * @ORM\Column(name="review", type="string")
	 */
	private $review;

	/**
	 * @var string
	 * @ORM\Column(name="platform", type="string")
	 */
	private $platform;

	/**
	 * @var integer
	 * @ORM\Column(name="rating", type="integer")
	 */
	private $rating;

	/**
	 * @var string
	 * @ORM\Column(name="sentiment", type="string")
	 */
	private $sentiment;

	/**
	 * @var float
	 * @ORM\Column(name="score_positive", type="float")
	 */
	private $scorePositive;

	/**
	 * @var float
	 * @ORM\Column(name="score_negative", type="float")
	 */
	private $scoreNegative;

	/**
	 * @var float
	 * @ORM\Column(name="score_mixed", type="float")
	 */
	private $scoreMixed;

	/**
	 * @var float
	 * @ORM\Column(name="score_neutral", type="float")
	 */
	private $scoreNeutral;

	/**
	 * @ORM\Column(name="metadata", type="json", nullable=false)
	 * @var array $metadata
	 */
	private $metadata;

	/**
	 * @var DateTime
	 * @ORM\Column(name="done_at", type="datetime")
	 */
	private $doneAt;

	/**
	 * @return UserProfile
	 */
	public function getProfile(): ?UserProfile
	{
		return $this->profile;
	}

	/**
	 * @return ReviewSettings
	 */
	public function getSettings(): ReviewSettings
	{
		return $this->reviewSettings;
	}

	public function getId(): UuidInterface
	{
		return $this->id;
	}

	public function getReview(): string
	{
		return $this->review;
	}

	/**
	 * @return DateTime|null
	 */
	private function nextReviewTime(): ?DateTime
	{
		if ($this->createdAt === null) {
			return null;
		}
		$lastStampTimestamp = $this
			->createdAt
			->getTimestamp();

		$cooldownDuration   = 604800;
		if ($cooldownDuration === null || $cooldownDuration === 0) {
			return null;
		}
		$nextReviewTimestamp = $lastStampTimestamp + $cooldownDuration;
		$nextReviewTime      = new DateTime();
		$nextReviewTime->setTimestamp($nextReviewTimestamp);
		return $nextReviewTime;
	}

	public function canReviewAtThisTime(): bool
	{
		$nextReviewTime = $this->nextReviewTime();
		if ($nextReviewTime === null) {
			return true;
		}
		$now = new DateTime();
		return $now >= $nextReviewTime;
	}

	/**
	 * @return UserReviewKeywords[]|ArrayCollection|Collection|Selectable
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	public function setDone(bool $done)
	{
		if ($done) {
			$this->doneAt = new DateTime();
		} else {
			$this->doneAt = null;
		}
	}

	public function getDone(): bool
	{
		if (is_null($this->doneAt)) {
			return false;
		} else {
			return true;
		}
	}

	public function setCreatedAtFromString(string $date)
	{
		$createdAt = new DateTime();
		$createdAt->setTimestamp($date);
		$this->createdAt = $createdAt;
	}

	public function setCreatedAt(DateTime $date)
	{
		$this->createdAt = $date;
	}

	public function jsonSerialize()
	{
		return [
			"id"        => $this->id,
			'profile'          => $this->getProfile(),
			"created_at" => $this->createdAt,
			'done' => $this->getDone(),
			"review_settings" => $this->reviewSettings,
			"rating" => $this->rating,
			"review" => $this->review,
			'keywords' => $this->getKeywords()->toArray(),
			"metadata" => $this->metadata,
			'platform' => $this->platform,
			'serial' => $this->getSettings()->getSerial(),
			'sentiment' => [
				"overall" => $this->sentiment,
				"score_positive" => $this->scorePositive,
				"score_negative" => $this->scoreNegative,
				"score_mixed" => $this->scoreMixed,
				"score_neutral" => $this->scoreNeutral,
			]
		];
	}
}
