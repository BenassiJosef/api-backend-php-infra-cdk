<?php


namespace App\Package\Loyalty\StampCard;


use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\UserProfile;
use App\Package\Loyalty\Reward\StubReward;
use Carbon\Traits\Date;
use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Twilio\Rest\Autopilot\V1\Assistant\StyleSheetContext;

class StubCard implements StorageStampCard
{
    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $card                        = new self(
            Uuid::fromString($data['schemeId']),
            $data['profileId'],
            $data['requiredStamps']
        );
        $card->id                    = Uuid::fromString($data['id']);
        $card->collectedStamps       = $data['collectedStamps'];
        $card->createdAt             = new DateTime($data['createdAt']);
        $activatedAt                 = $data['activatedAt'] ?? null;
        $lastStampedAt               = $data['lastStampedAt'] ?? null;
        $card->stampCooldownDuration = $data['stampCooldownDuration'];
        $redeemedAt                  = $data['redeemedAt'] ?? null;
        if ($activatedAt !== null) {
            $card->activatedAt = new DateTime($activatedAt);
        }
        if ($lastStampedAt !== null) {
            $card->lastStampedAt = new DateTime($lastStampedAt);
        }
        if ($redeemedAt !== null) {
            $card->redeemedAt = new DateTime($activatedAt);
        }
        return $card;
    }

    /**
     * StubCard constructor.
     * @param UuidInterface $schemeId
     * @param int $profileId
     * @param int $requiredStamps
     * @throws Exception
     */
    public function __construct(
        UuidInterface $schemeId,
        int $profileId,
        int $requiredStamps
    ) {
        $this->id              = Uuid::uuid4();
        $this->schemeId        = $schemeId;
        $this->profileId       = $profileId;
        $this->requiredStamps  = $requiredStamps;
        $this->collectedStamps = 0;
        $this->createdAt       = new DateTime();
    }

    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var UuidInterface $schemeId
     */
    private $schemeId;

    /**
     * @var int $profileId
     */
    private $profileId;

    /**
     * @var int $collectedStamps
     */
    private $collectedStamps;

    /**
     * @var int $requiredStamps
     */
    private $requiredStamps;

    /**
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @var DateTime | null $activatedAt
     */
    private $activatedAt;

    /**
     * @var DateTime | null $lastStampedAt
     */
    private $lastStampedAt;

    /**
     * @var int | null $stampCooldownDuration
     */
    private $stampCooldownDuration;

    /**
     * @var DateTime | null
     */
    private $redeemedAt;

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
     * @return int
     */
    public function getProfileId(): int
    {
        return $this->profileId;
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
    public function getCollectedStamps(): int
    {
        return $this->collectedStamps;
    }

    /**
     * @return int
     */
    public function getRemainingStamps(): int
    {
        return $this->requiredStamps - $this->collectedStamps;
    }

    /**
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->getRemainingStamps() === 0;
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
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activatedAt !== null;
    }

    /**
     * @return DateTime|null
     */
    public function getLastStampedAt(): ?DateTime
    {
        return $this->lastStampedAt;
    }

    /**
     * @return bool
     */
    public function canStampAtThisTime(): bool
    {
        $lastStampedAt = $this->lastStampedAt;
        if ($lastStampedAt === null) {
            return true;
        }
        $cooldownDuration = $this->stampCooldownDuration;
        if ($cooldownDuration === null) {
            return true;
        }
        $lastStampTimestamp = $lastStampedAt->getTimestamp();
        $nextStampTimestamp = $lastStampTimestamp + $cooldownDuration;
        $nextStampTime      = new DateTime();
        $nextStampTime->setTimestamp($nextStampTimestamp);
        $now = new DateTime();
        return $now >= $nextStampTime;
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

}