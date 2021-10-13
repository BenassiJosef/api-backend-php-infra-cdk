<?php


namespace App\Package\Loyalty\StampScheme;


use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\Reward\OutputReward;
use App\Package\Loyalty\Reward\RedeemableReward;
use App\Package\Loyalty\Reward\Reward;
use App\Package\Loyalty\Reward\StubReward;
use App\Package\Loyalty\StampCard\StampCard;
use App\Package\Loyalty\StampCard\StubCard;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Profile\StubUserProfile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;
use App\Package\Profile\MinimalUserProfile;

class StubSchemeUser implements SchemeUser, JsonSerializable
{
    /**
     * @param EntityManager $entityManager
     * @param array $data
     * @param EventNotifier|null $eventNotifier
     * @return static
     */
    public static function fromArray(
        EntityManager $entityManager,
        array $data,
        ?EventNotifier $eventNotifier = null
    ): self {
        /** @var int $requiredStamps */
        $requiredStamps = $data['requiredStamps'];
        $schemeId       = Uuid::fromString($data['schemeId']);
        $cards          = from(json_decode($data['cards'], JSON_OBJECT_AS_ARRAY))
            ->where(
                function (array $card): bool {
                    return $card['id'] !== null;
                }
            )
            ->select(
                function (array $card): StubCard {
                    return StubCard::fromArray($card);
                }
            )
            ->where(
                function (StubCard $card): bool {
                    return !$card->isRedeemed();
                }
            )
            ->toArray();

        $profile = new StubUserProfile();
        if (array_key_exists('id', $data)) {
            $profile = StubUserProfile::fromArray($data);
        }
        return new self(
            $entityManager,
            $schemeId,
            $profile,
            $requiredStamps,
            $cards,
            StubReward::fromArray(json_decode($data['reward'], JSON_OBJECT_AS_ARRAY)),
            $eventNotifier
        );
    }

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UuidInterface $schemeId
     */
    private $schemeId;

    /**
     * @var int $profileId
     */
    private $profileId;

    /**
     * @var StubUserProfile $profile
     */
    private $profile;

    /**
     * @var int $requiredStamps
     */
    private $requiredStamps;

    /**
     * @var StubCard[] $cards
     */
    private $cards;

    /**
     * @var Reward $reward
     */
    private $reward;

    /**
     * @var LazySchemeUser | null $lazySchemeUser
     */
    private $lazySchemeUser;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * StubSchemeUser constructor.
     * @param EntityManager $entityManager
     * @param UuidInterface $schemeId
     * @param StubUserProfile $profile
     * @param int $requiredStamps
     * @param StubCard[] $cards
     * @param Reward $reward
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        UuidInterface $schemeId,
        StubUserProfile $profile,
        int $requiredStamps,
        array $cards,
        Reward $reward,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->entityManager  = $entityManager;
        $this->schemeId       = $schemeId;
        $this->profileId      = $profile->getId();
        $this->profile        = $profile;
        $this->requiredStamps = $requiredStamps;
        $this->cards          = $cards;
        $this->reward         = $reward;
        $this->eventNotifier  = $eventNotifier;
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
     * @return MinimalUserProfile
     */
    public function getProfile(): MinimalUserProfile
    {
        return $this->profile;
    }

    /**
     * @return RedeemableReward[]
     */
    public function redeemableRewards(): array
    {
        if ($this->lazySchemeUser !== null) {
            return $this->lazySchemeUser->redeemableRewards();
        }
        $entityManager = $this->entityManager;
        return from($this->cards)
            ->where(
                function (StubCard $card): bool {
                    return $card->isFull();
                }
            )
            ->select(
                function (StubCard $card) use ($entityManager): RedeemableReward {
                    return new RedeemableReward($card, $entityManager);
                }
            )
            ->toValues()
            ->toArray();
    }

    /**
     * @return StampCard
     * @throws Throwable
     */
    public function currentCard(): StampCard
    {
        if ($this->lazySchemeUser !== null) {
            return $this->lazySchemeUser->currentCard();
        }

        $schemeId       = $this->schemeId;
        $profileId      = $this->profileId;
        $requiredStamps = $this->requiredStamps;
        return from($this->cards)
            ->where(
                function (StubCard $card): bool {
                    return !$card->isFull();
                }
            )
            ->select(
                function (StubCard $card): StampCard {
                    return new StampCard($card);
                }
            )
            ->firstOrFallback(
                function () use ($schemeId, $profileId, $requiredStamps): StampCard {
                    $card = new StubCard(
                        $schemeId,
                        $profileId,
                        $requiredStamps
                    );
                    return new StampCard(
                        $card
                    );
                }
            );
    }

    /**
     * @param UuidInterface $rewardId
     * @return RedeemableReward
     */
    public function getReward(UuidInterface $rewardId): ?RedeemableReward
    {
        $entityManager = $this->entityManager;
        return from($this->cards)
            ->where(
                function (StubCard $card) use ($rewardId): bool {
                    return $card->getId()->equals($rewardId)
                        && $card->isFull()
                        && !$card->isRedeemed();
                }
            )
            ->select(
                function (StubCard $card) use ($entityManager) : RedeemableReward {
                    return new RedeemableReward($card, $entityManager);
                }
            )
            ->firstOrDefault();
    }


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
    public function stamp(StampContext $context, int $stamps = 1)
    {
        $this->lazySchemeUser()->stamp($context, $stamps);
    }

    /**
     * @return LazySchemeUser
     * @throws ORMException
     */
    private function lazySchemeUser(): LazySchemeUser
    {
        if ($this->lazySchemeUser !== null) {
            return $this->lazySchemeUser;
        }
        /** @var LoyaltyStampScheme $stampScheme */
        $stampScheme = $this
            ->entityManager
            ->getReference(LoyaltyStampScheme::class, $this->schemeId);

        /** @var UserProfile $userProfile */
        $userProfile = $this
            ->entityManager
            ->getReference(UserProfile::class, $this->profileId);

        $this->lazySchemeUser = new LazySchemeUser(
            $this->entityManager,
            $stampScheme,
            $userProfile
        );
        return $this->lazySchemeUser;
    }

    /**
     * @return Reward
     */
    public function getSchemeReward(): Reward
    {
        return $this->reward;
    }


    /**
     * @return array|mixed
     * @throws Throwable
     */
    public function jsonSerialize()
    {
        return [
            'reward'            => new OutputReward($this->reward),
            'schemeId'          => $this->getSchemeId(),
            'profileId'         => $this->getProfileId(),
            'profile'           => $this->profile->jsonSerialize(),
            'currentCard'       => $this->currentCard(),
            'redeemableRewards' => $this->redeemableRewards(),
        ];
    }
}