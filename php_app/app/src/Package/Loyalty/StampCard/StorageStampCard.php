<?php

namespace App\Package\Loyalty\StampCard;

use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NotActiveException;
use App\Models\Loyalty\Exceptions\NotEnoughStampsException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\UserProfile;
use DateTime;
use Exception;
use Ramsey\Uuid\UuidInterface;

/**
 * Class LoyaltyReward
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_stamp_card")
 * @ORM\Entity
 */
interface StorageStampCard
{
    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface;

    /**
     * @return UuidInterface
     */
    public function getSchemeId(): UuidInterface;

    /**
     * @return int
     */
    public function getProfileId(): int;

    /**
     * @return int
     */
    public function getCollectedStamps(): int;

    /**
     * @return int
     */
    public function getRemainingStamps(): int;

    /**
     * @return bool
     */
    public function isFull(): bool;

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime;

    /**
     * @return DateTime|null
     */
    public function getActivatedAt(): ?DateTime;

    /**
     * @return bool
     */
    public function isActivated(): bool;

    /**
     * @return DateTime|null
     */
    public function getRedeemedAt(): ?DateTime;

    /**
     * @return DateTime|null
     */
    public function getLastStampedAt(): ?DateTime;

    /**
     * @return bool
     */
    public function canStampAtThisTime(): bool;

    /**
     * @return bool
     */
    public function isRedeemed(): bool;
}
