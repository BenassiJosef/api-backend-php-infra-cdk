<?php

namespace App\Models\Reviews;

use DateTimeImmutable;
use DoctrineExtensions\Query\Mysql\Date;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use DateTime;
use App\Models\Marketing\TemplateSettings;
use App\Models\Organization;

/**
 * Class ReviewSettings
 * @package App\Models\Reviews
 *
 * @ORM\Table(name="organization_review_settings")
 * @ORM\Entity
 */
class ReviewSettings implements JsonSerializable
{

	private static $setterMap = [
		'is_active'          => 'setIsActive',
		'title_text'         => 'setTitleText',
		'body_text'          => 'setBodyText',
		'subject'            => 'setSubject',
		'header_image'       => 'setHeaderImage',
		'background_image'   => 'setBackgroundImage',
		'send_after_seconds' => 'setSendAfterSecs',
		'text_alignment'     => 'setTextAlignment',
		'tripadvisor_url'    => 'setTripadvisorUrl',
		'facebook_page_id'   => 'setFacebookPageId',
		'google_page_id'     => 'setGooglePageId',
		'serial'             => 'setSerial',
		'sender_template'    => 'setSenderSettings',
		'happy_or_not' 		 => 'setHappyOrNot'
	];

	/**
	 * @param Organization $organization
	 * @param array $data
	 * @return static
	 * @throws Exception
	 */
	public static function fromArray(
		Organization $organization,
		array $data
	): self {

		$id = Uuid::uuid4();
		if (array_key_exists('id', $data) && $data['id'] !== null) {
			$id = Uuid::fromString($data['id']);
		}

		$settings = new self($organization, $id);
		$settings->updateFromArray($data);

		return $settings;
	}


	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid")
	 * @var UuidInterface $id
	 */
	private $id;

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
	 * @ORM\Column(name="created_at", type="datetime", nullable=false)
	 * @var DateTime $createdAt
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="edited_at", type="datetime", nullable=false)
	 * @var DateTime $editedAt
	 */
	private $editedAt;


	/**
	 * @ORM\Column(name="deleted_at", type="datetime", nullable=false)
	 * @var DateTime $deletedAt
	 */
	private $deletedAt;

	/**
	 * @ORM\Column(name="subject", type="string")
	 * @var string $subject
	 */
	private $subject;

	/**
	 * @ORM\Column(name="send_after_secs", type="integer")
	 * @var int $sendAfterSecs
	 */
	private $sendAfterSecs;

	/**
	 * @ORM\Column(name="header_image", type="string")
	 * @var string $header_image
	 */
	private $headerImage;

	/**
	 * @ORM\Column(name="background_image", type="string")
	 * @var string $background_image
	 */
	private $backgroundImage;

	/**
	 * @ORM\Column(name="title_text", type="string")
	 * @var string $titleText
	 */
	private $titleText;

	/**
	 * @ORM\Column(name="body_text", type="string")
	 * @var string $bodyText
	 */
	private $bodyText;

	/**
	 * @ORM\Column(name="text_alignment", type="string")
	 * @var string $textAlignment
	 */
	private $textAlignment;

	/**
	 * @ORM\Column(name="is_active", type="boolean", nullable=false)
	 * @var bool $isActive
	 */
	private $isActive;

	/**
	 * @ORM\Column(name="serial", type="string", nullable=false)
	 * @var string $serial
	 */
	private $serial;

	/**
	 * @ORM\Column(name="facebook_page_id", type="string", nullable=false)
	 * @var string $facebookPageId
	 */
	private $facebookPageId;

	/**
	 * @ORM\Column(name="happy_or_not", type="boolean", nullable=false)
	 * @var bool $happyOrNot
	 */
	private $happyOrNot;

	/**
	 * @ORM\Column(name="google_page_id", type="string", nullable=false)
	 * @var string $googlePageId
	 */
	private $googlePageId;

	/**
	 * @ORM\Column(name="tripadvisor_url", type="string", nullable=false)
	 * @var string $tripadvisorUrl
	 */
	private $tripadvisorUrl;


	/**
	 * @var string
	 * @ORM\Column(name="template_id", type="string", nullable=true)
	 */
	private $templateId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Marketing\TemplateSettings", cascade={"persist"})
	 * @ORM\JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
	 * @var TemplateSettings $template
	 */
	private $template;

	/**
	 * Website constructor.
	 * @param Organization $organization
	 * @throws Exception
	 */
	public function __construct(
		Organization $organization,
		?UuidInterface $id = null
	) {

		if (is_null($id)) {
			$this->id = Uuid::uuid4();
		} else {
			$this->id = $id;
		}

		$this->organizationId = $organization->getId();
		$this->organization   = $organization;
		$this->createdAt      = new DateTime();
		$this->deletedAt      = null;

		$this->touch();
	}

	/**
	 * @param array $data
	 * @return self
	 */
	public function updateFromArray(array $data): self
	{
		foreach ($data as $key => $value) {
			if (!array_key_exists($key, self::$setterMap)) {
				continue;
			}
			$setter = self::$setterMap[$key];
			$this->$setter($value);
		}
		return $this;
	}

	public function touch()
	{
		$this->editedAt = new DateTime();
	}

	public function setSubject(?string $subject)
	{
		$this->subject = $subject;
		$this->touch();
	}

	public function setSendAfterSeconds(int $sendAfterSeconds = 86400)
	{
		$this->sendAfterSeconds = $sendAfterSeconds;
		$this->touch();
	}

	public function setHeaderImage(string $headerImage)
	{
		$this->headerImage = $headerImage;
		$this->touch();
	}

	public function setBackgroundImage(?string $backgroundImage)
	{
		$this->backgroundImage = $backgroundImage;
		$this->touch();
	}

	public function setTitleText(string $titleText)
	{
		$this->titleText = $titleText;
		$this->touch();
	}

	public function setBodyText(string $bodyText)
	{
		$this->bodyText = $bodyText;
		$this->touch();
	}

	public function setIsActive(bool $isActive)
	{
		$this->isActive = $isActive;
		$this->touch();
	}

	public function setHappyOrNot(bool $happyOrNot)
	{
		$this->happyOrNot = $happyOrNot;
		$this->touch();
	}

	public function setFacebookPageId(?string $facebookPageId)
	{
		$this->facebookPageId = $facebookPageId;
		$this->touch();
	}

	public function setTripadvisorUrl(?string $tripadvisorUrl)
	{
		$this->tripadvisorUrl = $tripadvisorUrl;
		$this->touch();
	}

	public function setGooglePageId(?string $googlePageId)
	{
		$this->googlePageId = $googlePageId;
		$this->touch();
	}

	public function setTextAlignment(string $textAlignment)
	{
		$this->textAlignment = $textAlignment;
		$this->touch();
	}

	public function setSenderSettings(?array $template)
	{
		if (!$template) {
			$this->templateId = null;
		} else {
			$this->templateId = $template['id'];
		}
	}

	public function setTemplate(TemplateSettings $template)
	{
		$this->template   = $template;
		$this->templateId = $this->template->getId();
		$this->touch();
	}

	public function setTemplateId(string $templateId)
	{
		$this->templateId = $templateId;
	}

	public function setSerial(?string $serial)
	{
		$this->serial = $serial;
		$this->touch();
	}

	public function setSendAfterSecs(?int $sendAfterSecs)
	{
		$this->sendAfterSecs = $sendAfterSecs;
	}

	public function setDeleted(bool $isDeleted)
	{
		if ($isDeleted) {
			$this->deletedAt = new DateTime();
		} else {
			$this->deletedAt = null;
		}

		$this->touch();
	}

	public function getId(): UuidInterface
	{
		return $this->id;
	}

	public function getTemplateId(): ?string
	{
		if (is_null($this->template)) {
			return null;
		}
		return $this->template->getId();
	}

	public function getOrganization(): Organization
	{
		return $this->organization;
	}

	public function getSerial(): ?string
	{
		return $this->serial;
	}

	public function getTripadvisorUrl(): ?string
	{
		return $this->tripadvisorUrl;
	}

	public function getTripadvisorReviewLink(): ?string
	{
		if (!$this->getTripadvisorUrl()) {
			return null;
		}
		return $this->getTripadvisorUrl();
	}

	public function getFacebookPageId(): ?string
	{
		return $this->facebookPageId;
	}

	public function getFacebookReviewLink(): ?string
	{
		if (!$this->getFacebookPageId()) {
			return null;
		}
		return "https://www.facebook.com/" . $this->getFacebookPageId() . "/reviews";
	}

	public function getGooglePlaceId(): ?string
	{
		return $this->googlePageId;
	}

	public function getGoogleReviewLink(): ?string
	{
		if (!$this->getGooglePlaceId()) {
			return null;
		}
		return "https://search.google.com/local/writereview?placeid=" . $this->getGooglePlaceId();
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	/**
	 * @return int
	 */
	public function getSendAfterSecs(): int
	{
		return $this->sendAfterSecs;
	}

	public function getSendTime(): DateTimeImmutable
	{
		$now = new DateTimeImmutable();
		return $now
			->setTimestamp(
				$now->getTimestamp() + $this->sendAfterSecs
			);
	}

	public function getOrganizationId()
	{
		return $this->organizationId->toString();
	}

	public function reviewStarLinks()
	{
		$platforms = array_filter(
			[
				$this->getFacebookReviewLink(),
				$this->getGoogleReviewLink(),
				$this->getTripadvisorReviewLink()
			]
		);
		$id        = $this->id->toString();
		$baseUrl   = "https://reviews.stampede.ai/${id}";

		$links = [];
		for ($i = 1; $i <= 5; $i++) {
			if ($i <= 3 || count($platforms) === 0) {
				$links["link${i}"] = $baseUrl . "?rating=${i}";
				continue;
			}
			$links["link${i}"] = $platforms[array_rand($platforms)];
		}
		return $links;
	}

	public function emailArray()
	{
		if (is_null($this->template)) {
			return array_merge($this->reviewStarLinks(), $this->jsonSerialize());
		} else {
			return array_merge($this->reviewStarLinks(), $this->jsonSerialize(), $this->template->emailArray());
		}
	}

	public function hasValidSubscription(): bool
	{

		$subscription = $this->getOrganization()->getSubscription();
		if (is_null($subscription)) {
			return false;
		}

		return $subscription->hasAddon($subscription::ADDON_REVIEWS);
	}

	public function getHappyOrNot(): bool
	{
		if (is_null($this->happyOrNot)) {
			return false;
		}
		return $this->happyOrNot;
	}

	public function getIsActive(): bool
	{
		return $this->isActive;
	}


	public function jsonSerialize()
	{
		return [
			"id"                 => $this->id->toString(),
			"organization_id"    => $this->organizationId->toString(),
			"edited_at"          => $this->editedAt,
			"created_at"         => $this->createdAt,
			"deleted_at"         => $this->deletedAt,
			"subject"            => $this->subject,
			"sender_template"    => $this->template,
			"send_after_seconds" => $this->sendAfterSecs,
			"header_image"       => $this->headerImage,
			"background_image"   => $this->backgroundImage,
			"title_text"         => $this->titleText,
			"body_text"          => $this->bodyText,
			"text_alignment"     => $this->textAlignment,
			"is_active"          => $this->getIsActive(),
			"facebook_page_id"   => $this->getFacebookPageId(),
			"google_page_id"     => $this->getGooglePlaceId(),
			"tripadvisor_url"    => $this->getTripadvisorUrl(),
			"serial"             => $this->serial,
			'has_valid_subscription' => $this->hasValidSubscription(),
			'happy_or_not' => $this->getHappyOrNot()
		];
	}
}
