<?php


namespace App\Package\Loyalty\StampCard;


use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\UserProfile;
use App\Package\Loyalty\Reward\Reward;
use DateTime;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

class StampCard implements JsonSerializable
{
    /**
     * @var StorageStampCard $stampCard
     */
    private $stampCard;

    /**
     * StampCard constructor.
     * @param StorageStampCard $stampCard
     */
    public function __construct(StorageStampCard $stampCard)
    {
        $this->stampCard = $stampCard;
    }

    /**
     * @return UuidInterface
     */
    public function getSchemeId(): UuidInterface
    {
        return $this->stampCard->getSchemeId();
    }

    /**
     * @return int
     */
    public function getProfileId(): int
    {
        return $this->stampCard->getProfileId();
    }

    /**
     * @return int
     */
    public function getCollectedStamps(): int
    {
        return $this->stampCard->getCollectedStamps();
    }

    /**
     * @return int
     */
    public function getRemainingStamps(): int
    {
        return $this->stampCard->getRemainingStamps();
    }

    /**
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->stampCard->isFull();
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->stampCard->getCreatedAt();
    }

    /**
     * @return DateTime|null
     */
    public function getActivatedAt(): ?DateTime
    {
        return $this->stampCard->getActivatedAt();
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->stampCard->isActivated();
    }

    /**
     * @return DateTime|null
     */
    public function getLastStampedAt(): ?DateTime
    {
        return $this->stampCard->getLastStampedAt();
    }

    /**
     * @return bool
     */
    public function canStampAtThisTime(): bool
    {
        return $this->stampCard->canStampAtThisTime();
    }

    /**
     * @return DateTime|null
     */
    public function getRedeemedAt(): ?DateTime
    {
        return $this->stampCard->getActivatedAt();
    }

    /**
     * @return bool
     */
    public function isRedeemed(): bool
    {
        return $this->stampCard->isRedeemed();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'schemeId'           => $this->getSchemeId(),
            'profileId'          => $this->getProfileId(),
            'collectedStamps'    => $this->getCollectedStamps(),
            'remainingStamps'    => $this->getRemainingStamps(),
            'isFull'             => $this->isFull(),
            'createdAt'          => $this->getCreatedAt(),
            'lastStampedAt'      => $this->getLastStampedAt(),
            'canStampAtThisTime' => $this->canStampAtThisTime(),
            'activatedAt'        => $this->getActivatedAt(),
            'isActive'           => $this->isActivated(),
        ];
    }
}