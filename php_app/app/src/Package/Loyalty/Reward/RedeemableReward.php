<?php


namespace App\Package\Loyalty\Reward;

use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\NotActiveException;
use App\Models\Loyalty\Exceptions\NotEnoughStampsException;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Loyalty\StampCard\StorageStampCard;
use App\Package\Loyalty\StampScheme\Redeemable;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

class RedeemableReward implements JsonSerializable, StorageStampCard
{
    /**
     * @var StorageStampCard $stampCard
     */
    private $stampCard;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * RedeemableReward constructor.
     * @param StorageStampCard $stampCard
     * @param EntityManager $entityManager
     */
    public function __construct(
        StorageStampCard $stampCard,
        EntityManager $entityManager
    ) {
        $this->stampCard     = $stampCard;
        $this->entityManager = $entityManager;
    }


    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->stampCard->getId();
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
    public function getRedeemedAt(): ?DateTime
    {
        return $this->stampCard->getActivatedAt();
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
        return $this->stampCard->canStampAtThisTime() && !$this->isFull();
    }

    /**
     * @param OauthUser|null $redeemer
     * @return LoyaltyReward
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws AlreadyRedeemedException
     */
    public function redeem(OauthUser $redeemer = null): LoyaltyReward
    {
        if (!$this->stampCard instanceof Redeemable) {
            $this->stampCard = $this
                ->entityManager
                ->find(LoyaltyStampCard::class, $this->getId());
        }
        $reward = $this->stampCard->redeem($redeemer);
        $this->entityManager->persist($this->stampCard);
        $this->entityManager->flush();
        return $reward;
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
            'id'                 => $this->getId()->toString(),
            'profileId'          => $this->getProfileId(),
            'schemeId'           => $this->getSchemeId(),
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
}