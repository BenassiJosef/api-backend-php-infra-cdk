<?php


namespace App\Package\Loyalty\StampScheme;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NegativeStampException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\Reward\OutputReward;
use App\Package\Loyalty\Reward\RedeemableReward;
use App\Package\Loyalty\Reward\Reward;
use App\Package\Loyalty\StampCard\StampCard;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Profile\MinimalUserProfile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;
use Throwable;

class LazySchemeUser implements JsonSerializable, SchemeUser
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var LoyaltyStampScheme $stampScheme
	 */
	private $stampScheme;

	/**
	 * @var UserProfile $userProfile
	 */
	private $userProfile;

	/**
	 * @var EventNotifier $eventNotifier
	 */
	private $eventNotifier;

	/** @var OrganizationRegistration $registration */
	private $registration;

	/**
	 * SchemeUser constructor.
	 * @param EntityManager $entityManager
	 * @param LoyaltyStampScheme $stampScheme
	 * @param UserProfile $userProfile
	 * @param EventNotifier|null $eventNotifier
	 */
	public function __construct(
		EntityManager $entityManager,
		LoyaltyStampScheme $stampScheme,
		UserProfile $userProfile,
		?EventNotifier $eventNotifier = null
	) {
		if ($eventNotifier === null) {
			$eventNotifier = new NopNotifier();
		}
		$this->entityManager = $entityManager;
		$this->stampScheme   = $stampScheme;
		$this->userProfile   = $userProfile;
		$this->eventNotifier = $eventNotifier;
	}

	/**
	 * @return UuidInterface
	 */
	public function getSchemeId(): UuidInterface
	{
		return $this->stampScheme->getId();
	}

	/**
	 * @return int
	 */
	public function getProfileId(): int
	{
		return $this->userProfile->getId();
	}

	/**
	 * @return RedeemableReward[]
	 */
	public function redeemableRewards(): array
	{
		$entityManager = $this->entityManager;
		return from($this->redeemableCards())
			->select(
				function (LoyaltyStampCard $card) use ($entityManager): RedeemableReward {
					return new RedeemableReward($card, $entityManager);
				}
			)
			->toArray();
	}

	/**
	 * @return MinimalUserProfile
	 */
	public function getProfile(): MinimalUserProfile
	{
		return $this->userProfile;
	}

	/**
	 * @return LoyaltyStampCard[]
	 */
	private function redeemableCards(): array
	{
		$qb            = $this
			->entityManager
			->createQueryBuilder();
		$expr          = $qb->expr();
		$query         = $qb
			->select('rc')
			->from(LoyaltyStampCard::class, 'rc')
			->leftJoin(
				LoyaltyStampScheme::class,
				'lss',
				Join::WITH,
				'lss.id = rc.schemeId'
			)
			->where(
				$expr->andX(
					$expr->eq('rc.schemeId', ':schemeId'),
					$expr->eq('rc.profileId', ':profileId'),
					$expr->eq('rc.collectedStamps', 'lss.requiredStamps'),
					$expr->isNull('rc.redeemedAt'),
					$expr->isNull('rc.deletedAt'),
					$expr->isNull('lss.deletedAt')
				)
			)
			->setParameters(
				[
					'schemeId'  => $this->stampScheme->getId(),
					'profileId' => $this->userProfile->getId(),
				]
			)
			->getQuery();
		$eventNotifier = $this->eventNotifier;
		return from($query->getResult())
			->select(
				function (LoyaltyStampCard $card) use ($eventNotifier) {
					return $card->setEventNotifier($eventNotifier);
				}
			)
			->toArray();
	}

	/**
	 * @return StampCard
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws Throwable
	 */
	public function currentCard(): StampCard
	{
		$curr = $this->currentLoyaltyStampCard();
		return new StampCard($curr);
	}

	/**
	 * @return LoyaltyStampCard
	 * @throws NonUniqueResultException
	 * @throws Throwable
	 */
	private function currentLoyaltyStampCard(): LoyaltyStampCard
	{
		$qb   = $this
			->entityManager
			->createQueryBuilder();
		$expr = $qb->expr();


		$this->entityManager->beginTransaction();
		/** @var LoyaltyStampCard | null $activeCard */
		$query      = $qb
			->select('ac')
			->from(LoyaltyStampCard::class, 'ac')
			->leftJoin(
				LoyaltyStampScheme::class,
				'lss',
				Join::WITH,
				'lss.id = ac.schemeId'
			)
			->where(
				$expr->andX(
					$expr->eq('ac.schemeId', ':schemeId'),
					$expr->eq('ac.profileId', ':profileId'),
					$expr->lt('ac.collectedStamps', 'lss.requiredStamps'),
					$expr->isNull('ac.redeemedAt'),
					$expr->isNull('ac.deletedAt')
				)
			)
			->setParameters(
				[
					'schemeId'  => $this->stampScheme->getId(),
					'profileId' => $this->userProfile->getId(),
				]
			)
			->setMaxResults(1)
			->getQuery();
		$activeCard = null;
		try {
			$activeCard = $query->getSingleResult();
		} catch (NoResultException $exception) {
			// Ignore it, we'll create one.
		}

		if ($activeCard === null) {
			try {
				$activeCard = $this->newCard();
			} catch (Throwable $throwable) {
				$this->entityManager->rollback();
				throw $throwable;
			}
		}
		$this->entityManager->commit();
		return $activeCard->setEventNotifier($this->eventNotifier);
	}

	/**
	 * @return LoyaltyStampCard
	 * @throws Exception
	 */
	private function newCard(): LoyaltyStampCard
	{
		/** @var OrganizationRegistration $registration */
		$this->registration = $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy(
			[
				'profileId'      => $this->getProfileId(),
				'organizationId' => $this->stampScheme->getOrganizationId()
			]
		);
		if (is_null($this->registration)) {
			$this->registration = new OrganizationRegistration($this->stampScheme->getOrganization(), $this->userProfile);
		}
		return new LoyaltyStampCard(
			$this->stampScheme,
			$this->registration,
			0,
			$this->eventNotifier
		);
	}

	/**
	 * @param int $stamps
	 * @param StampContext|null $context
	 * @throws AlreadyRedeemedException
	 * @throws FullCardException
	 * @throws NegativeStampException
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws OverstampedCardException
	 * @throws Throwable
	 * @throws AlreadyActivatedException
	 * @throws StampedTooRecentlyException
	 */
	public function stamp(StampContext $context, int $stamps = 1)
	{
		if ($stamps === 0) {
			return;
		}
		$currentCard = $this->currentLoyaltyStampCard();
		if ($stamps < 0) {
			throw new NegativeStampException($currentCard);
		}
		while ($stamps > 0) {
			$remainingStamps = $currentCard->getRemainingStamps();
			$availableStamps = min($stamps, $remainingStamps);
			$stamps          -= $availableStamps;
			$currentCard->stamp($context, $availableStamps);
			$this->entityManager->persist($currentCard);
			$this->entityManager->flush();
			$currentCard = $this->currentLoyaltyStampCard();
		}
	}

	/**
	 * @param UuidInterface $rewardId
	 * @return RedeemableReward|null
	 * @throws NonUniqueResultException
	 */
	public function getReward(UuidInterface $rewardId): ?RedeemableReward
	{
		$qb   = $this
			->entityManager
			->createQueryBuilder();
		$expr = $qb
			->expr();

		$query = $qb
			->select('r')
			->from(LoyaltyStampCard::class, 'r')
			->where(
				$expr->andX(
					$expr->eq('r.schemeId', ':schemeId'),
					$expr->eq('r.id', ':id'),
					$expr->isNull('r.redeemedAt'),
					$expr->isNull('r.deletedAt')
				)
			)
			->setParameters(
				[
					'schemeId' => $this->stampScheme->getId(),
					'id'       => $rewardId,
				]
			)
			->getQuery();

		/** @var LoyaltyStampCard | null $rewardCard */
		$rewardCard = null;
		try {
			$rewardCard = $query->getSingleResult();
		} catch (NoResultException $exception) {
			return null;
		}
		return new RedeemableReward(
			$rewardCard->setEventNotifier($this->eventNotifier),
			$this->entityManager
		);
	}

	/**
	 * @return Reward
	 */
	public function getSchemeReward(): Reward
	{
		return $this->stampScheme->getReward();
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @throws NonUniqueResultException
	 * @throws ORMException
	 * @throws Throwable
	 * @since 5.4
	 */
	public function jsonSerialize()
	{
		return [
			'reward'            => new OutputReward($this->getSchemeReward()),
			'schemeId'          => $this->stampScheme->getId()->toString(),
			'profile'           => $this->userProfile->jsonSerialize(),
			'profileId'         => $this->userProfile->getId(),
			'currentCard'       => $this->currentCard(),
			'redeemableRewards' => $this->redeemableRewards(),
		];
	}
}
