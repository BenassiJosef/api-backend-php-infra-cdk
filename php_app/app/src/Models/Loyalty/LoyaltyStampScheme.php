<?php


namespace App\Models\Loyalty;

use App\Models\Organization;
use DateTime;
use DoctrineExtensions\Query\Mysql\Date;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LoyaltyReward
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_stamp_scheme")
 * @ORM\Entity
 */
class LoyaltyStampScheme implements \JsonSerializable
{
	private static $setterMap = [
		'isActive'         => 'setIsActive',
		'backgroundColour' => 'setBackgroundColour',
		'foregroundColour' => 'setForegroundColour',
		'labelColour'      => 'setLabelColour',
		'labelIcon'        => 'setLabelIcon',
		'icon'             => 'setIcon',
		'backgroundImage'  => 'setBackgroundImage',
		'isDefault'        => 'setIsDefault',
		'terms'            => 'setTerms',
		'stampCooldownDuration' => 'setStampCooldownDuration',
		'serial' => 'setSerial'
	];

	/**
	 * @param Organization $organization
	 * @param LoyaltyReward $loyaltyReward
	 * @param array $data
	 * @return static
	 * @throws Exception
	 */
	public static function fromArray(
		Organization $organization,
		LoyaltyReward $loyaltyReward,
		array $data
	): self {
		$scheme                        = new self(
			$organization,
			$loyaltyReward,
			(int)($data['requiredStamps'] ?? 6),
			$data['isActive'] ?? true,
			$data['serial'] ?? null
		);
		$scheme->stampCooldownDuration = (int)($data['stampCooldownDuration'] ?? 60 * 60);
		$scheme->backgroundColour      = $data['backgroundColour'] ?? 'rgb(255, 58, 155)';
		$scheme->foregroundColour      = $data['foregroundColour'] ?? 'rgb(255, 255, 255)';
		$scheme->labelColour           = $data['labelColour'] ?? null;
		$scheme->labelIcon           = $data['labelIcon'] ?? null;
		$scheme->icon                  = $data['icon'] ?? null;
		$scheme->backgroundImage       = $data['backgroundImage'] ?? null;
		$scheme->isDefault             = $data['isDefault'] ?? false;
		$scheme->terms                 = $data['terms'] ?? null;
		return $scheme;
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid")
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
	 * @ORM\Column(name="reward_id", type="uuid", nullable=false)
	 * @var UuidInterface $rewardId
	 */
	private $rewardId;

	/**
	 * @ORM\ManyToOne(targetEntity="LoyaltyReward", cascade={"persist"})
	 * @ORM\JoinColumn(name="reward_id", referencedColumnName="id", nullable=false)
	 * @var LoyaltyReward $reward
	 */
	private $reward;

	/**
	 * @ORM\Column(name="serial", type="string", nullable=true)
	 * @var string $serial
	 */
	private $serial;

	/**
	 * @ORM\Column(name="required_stamps", type="integer", nullable=false)
	 * @var int $requiredStamps
	 */
	private $requiredStamps;

	/**
	 * @ORM\Column(name="terms", type="string", nullable=true)
	 * @var string | null $terms
	 */
	private $terms;

	/**
	 * @ORM\Column(name="stamp_cooldown_duration", type="integer", nullable=true)
	 * @var int $stampCooldownDuration
	 */
	private $stampCooldownDuration;

	/**
	 * @ORM\Column(name="background_colour", type="string", nullable=true)
	 * @var string | null $backgroundColour
	 */
	private $backgroundColour;

	/**
	 * @ORM\Column(name="foreground_colour", type="string", nullable=true)
	 * @var string | null $foregroundColour
	 */
	private $foregroundColour;

	/**
	 * @ORM\Column(name="label_colour", type="string", nullable=true)
	 * @var string | null $labelColour
	 */
	private $labelColour;


	/**
	 * @ORM\Column(name="label_icon", type="string", nullable=true)
	 * @var string | null $labelIcon
	 */
	private $labelIcon;

	/**
	 * @ORM\Column(name="icon", type="string", nullable=true)
	 * @var string | null $icon
	 */
	private $icon;

	/**
	 * @ORM\Column(name="background_image", type="string", nullable=true)
	 * @var string | null $backgroundImage
	 */
	private $backgroundImage;

	/**
	 * @ORM\Column(name="is_active", type="boolean", nullable=false)
	 * @var bool $isActive
	 */
	private $isActive;

	/**
	 * @ORM\Column(name="is_default", type="boolean", nullable=false)
	 * @var bool | null $isDefault
	 */
	private $isDefault;

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
	 * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
	 * @var DateTime | null $deletedAt
	 */
	private $deletedAt;

	/**
	 * LoyaltyStampScheme constructor.
	 * @param Organization $organization
	 * @param LoyaltyReward $reward
	 * @param int $requiredStamps
	 * @param bool $isActive
	 *
	 * @param string|null $serial
	 * @throws Exception
	 */
	public function __construct(
		Organization $organization,
		LoyaltyReward $reward,
		int $requiredStamps = 6,
		bool $isActive = true,
		string $serial = null
	) {
		$this->id                    = Uuid::uuid1();
		$this->organizationId        = $organization->getId();
		$this->organization          = $organization;
		$this->rewardId              = $reward->getId();
		$this->reward                = $reward;
		$this->serial                = $serial;
		$this->requiredStamps        = $requiredStamps;
		$this->stampCooldownDuration = 60 * 60;
		$this->backgroundColour      = 'rgb(255, 58, 155)';
		$this->foregroundColour      = 'rgb(255, 255, 255)';
		$this->isActive              = $isActive;
		$this->isDefault             = false;
		$this->createdAt             = new DateTime();
		$this->editedAt              = new DateTime();
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
	 * @return UuidInterface
	 */
	public function getRewardId(): UuidInterface
	{
		return $this->rewardId;
	}

	/**
	 * @return LoyaltyReward
	 */
	public function getReward(): LoyaltyReward
	{
		return $this->reward;
	}

	/**
	 * @return string
	 */
	public function getSerial(): string
	{
		return $this->serial;
	}

	/**
	 * @return int
	 */
	public function getRequiredStamps(): int
	{
		return $this->requiredStamps;
	}

	/**
	 * @return int
	 */
	public function getStampCooldownDuration(): int
	{
		return $this->stampCooldownDuration;
	}

	/**
	 * @return string|null
	 */
	public function getTerms(): ?string
	{
		return $this->terms;
	}

	/**
	 * @return string|null
	 */
	public function getBackgroundColour(): ?string
	{
		return $this->backgroundColour;
	}

	/**
	 * @return string|null
	 */
	public function getForegroundColour(): ?string
	{
		return $this->foregroundColour;
	}

	/**
	 * @return string|null
	 */
	public function getLabelColour(): ?string
	{
		return $this->labelColour;
	}

	/**
	 * @return string|null
	 */
	public function getIcon(): ?string
	{
		return $this->icon;
	}

	/**
	 * @return string|null
	 */
	public function getLabelIcon(): ?string
	{
		return $this->labelIcon;
	}

	/**
	 * @return string|null
	 */
	public function getBackgroundImage(): ?string
	{
		return $this->backgroundImage;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool
	{
		return $this->isActive;
	}

	/**
	 * @return bool|null
	 */
	public function getIsDefault(): ?bool
	{
		return $this->isDefault;
	}

	/**
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @return DateTime
	 */
	public function getEditedAt(): DateTime
	{
		return $this->editedAt;
	}

	/**
	 * @return DateTime|null
	 */
	public function getDeletedAt(): ?DateTime
	{
		return $this->deletedAt;
	}

	public function delete()
	{
		$this->deletedAt = new DateTime();
		$this->edited();
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

	/**
	 * @param string|null $terms
	 * @return LoyaltyStampScheme
	 */
	public function setTerms(?string $terms): LoyaltyStampScheme
	{
		$this->terms = $terms;
		return $this;
	}

	/**
	 * @param string $stampCooldownDuration
	 * @return LoyaltyStampScheme
	 */
	public function setStampCooldownDuration(string $stampCooldownDuration): LoyaltyStampScheme
	{
		$this->stampCooldownDuration = $stampCooldownDuration;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $backgroundColour
	 * @return LoyaltyStampScheme
	 */
	public function setBackgroundColour(string $backgroundColour): LoyaltyStampScheme
	{
		$this->backgroundColour = $backgroundColour;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $foregroundColour
	 * @return LoyaltyStampScheme
	 */
	public function setForegroundColour(string $foregroundColour): LoyaltyStampScheme
	{
		$this->foregroundColour = $foregroundColour;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $labelColour
	 * @return LoyaltyStampScheme
	 */
	public function setLabelColour(string $labelColour): LoyaltyStampScheme
	{
		$this->labelColour = $labelColour;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $labelColour
	 * @return LoyaltyStampScheme
	 */
	public function setLabelIcon(string $labelIcon): LoyaltyStampScheme
	{
		$this->labelIcon = $labelIcon;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $icon
	 * @return LoyaltyStampScheme
	 */
	public function setIcon(string $icon): LoyaltyStampScheme
	{
		$this->icon = $icon;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $backgroundImage
	 * @return LoyaltyStampScheme
	 */
	public function setBackgroundImage(string $backgroundImage): LoyaltyStampScheme
	{
		$this->backgroundImage = $backgroundImage;
		$this->edited();
		return $this;
	}

	/**
	 * @param string $serial
	 * @return LoyaltyStampScheme
	 */
	public function setSerial(string $serial): LoyaltyStampScheme
	{
		$this->serial = $serial;
		$this->edited();
		return $this;
	}

	/**
	 * @param bool $isActive
	 * @return LoyaltyStampScheme
	 */
	public function setIsActive(bool $isActive): LoyaltyStampScheme
	{
		$this->isActive = $isActive;
		$this->edited();
		return $this;
	}

	/**
	 * @param bool $isDefault
	 * @return LoyaltyStampScheme
	 */
	public function setIsDefault(bool $isDefault): LoyaltyStampScheme
	{
		$this->isDefault = $isDefault;
		$this->edited();
		return $this;
	}

	private function edited()
	{
		$this->editedAt = new DateTime();
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize()
	{
		return [
			'id'                    => $this->id->toString(),
			'organizationId'        => $this->organizationId->toString(),
			'reward'                => $this->reward,
			'serial'                => $this->serial,
			'terms'                 => $this->terms,
			'stampCooldownDuration' => $this->stampCooldownDuration,
			'backgroundColour'      => $this->backgroundColour,
			'foregroundColour'      => $this->foregroundColour,
			'labelColour'           => $this->labelColour,
			'labelIcon'             => $this->labelIcon,
			'icon'                  => $this->icon,
			'backgroundImage'       => $this->backgroundImage,
			'requiredStamps'        => $this->requiredStamps,
			'isActive'              => $this->isActive,
			'isDefault'             => $this->isDefault,
			'createdAt'             => $this->createdAt,
			'editedAt'              => $this->editedAt,
		];
	}
}
