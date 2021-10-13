<?php

namespace App\Package\Loyalty\StampScheme;

use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\OauthUser;
use App\Package\Loyalty\Reward\RedeemableReward;
use App\Package\Loyalty\Reward\Reward;
use App\Package\Loyalty\StampCard\StampCard;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Profile\MinimalUserProfile;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;
use Throwable;

interface SchemeUser
{

    /**
     * @return UuidInterface
     */
    public function getSchemeId(): UuidInterface;

    /**
     * @return int
     */
    public function getProfileId(): int;

    /**
     * @return MinimalUserProfile
     */
    public function getProfile(): MinimalUserProfile;

    /**
     * @return RedeemableReward[]
     */
    public function redeemableRewards(): array;

    /**
     * @return StampCard
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws Throwable
     */
    public function currentCard(): StampCard;

    /**
     * @param int $stamps
     * @param StampContext|null $context
     * @throws AlreadyRedeemedException
     * @throws FullCardException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws OverstampedCardException
     * @throws StampedTooRecentlyException
     * @throws Throwable
     */
    public function stamp(StampContext $context, int $stamps = 1);

    /**
     * @param UuidInterface $rewardId
     * @return RedeemableReward
     */
    public function getReward(UuidInterface $rewardId): ?RedeemableReward;

    /**
     * @return Reward
     */
    public function getSchemeReward(): Reward;
}
