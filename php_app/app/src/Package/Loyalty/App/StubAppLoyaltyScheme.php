<?php


namespace App\Package\Loyalty\App;

use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Organization;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\Reward\OutputReward;
use App\Package\Loyalty\Reward\RedeemableReward;
use App\Package\Loyalty\Reward\Reward;
use App\Package\Loyalty\StampCard\StampCard;
use App\Package\Loyalty\StampCard\StubCard;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Loyalty\StampScheme\SchemeUser;
use App\Package\Loyalty\StampScheme\StubSchemeUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Class StubAppLoyaltyScheme
 * @package App\Package\Loyalty\App
 */
class StubAppLoyaltyScheme implements AppLoyaltyScheme, JsonSerializable
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
		$decodedLocations = json_decode($data['locations'], JSON_OBJECT_AS_ARRAY);
		/** @var LoyaltyLocation[] $locations */
		$locations     = from($decodedLocations)
			->where(function (array $data) {
				return !is_null($data['serial']);
			})
			->select(
				function (array $data): LoyaltyLocation {
					return LoyaltyLocation::fromArray($data);
				}
			)
			->toArray();
		$scheme        = new self(
			$entityManager,
			Uuid::fromString($data['schemeId']),
			LoyaltyOrganization::fromArray($data),
			$locations,
			LoyaltyBranding::fromArray($data),
			StubSchemeUser::fromArray($entityManager, $data, $eventNotifier),
			$eventNotifier
		);
		$scheme->terms = $data['terms'] ?? null;
		return $scheme;
	}

	/**
	 * @var LoyaltyOrganization $organization
	 */
	private $organization;

	/**
	 * @var UuidInterface $schemeId
	 */
	private $schemeId;

	/**
	 * @var LoyaltyLocation[] $locations
	 */
	private $locations;

	/**
	 * @var LoyaltyBranding $branding
	 */
	private $branding;

	/**
	 * @var string | null $terms
	 */
	private $terms;

	/**
	 * @var SchemeUser $baseSchemeUser
	 */
	private $baseSchemeUser;

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var EventNotifier $eventNotifier
	 */
	private $eventNotifier;

	/**
	 * StubAppLoyaltyScheme constructor.
	 * @param EntityManager $entityManager
	 * @param UuidInterface $schemeId
	 * @param LoyaltyOrganization $organization
	 * @param LoyaltyLocation[] $locations
	 * @param LoyaltyBranding $branding
	 * @param SchemeUser $baseSchemeUser
	 * @param EventNotifier|null $eventNotifier
	 */
	public function __construct(
		EntityManager $entityManager,
		UuidInterface $schemeId,
		LoyaltyOrganization $organization,
		array $locations,
		LoyaltyBranding $branding,
		SchemeUser $baseSchemeUser,
		?EventNotifier $eventNotifier = null
	) {
		if ($eventNotifier === null) {
			$eventNotifier = new NopNotifier();
		}
		$this->entityManager  = $entityManager;
		$this->schemeId       = $schemeId;
		$this->organization   = $organization;
		$this->locations      = $locations;
		$this->branding       = $branding;
		$this->baseSchemeUser = $baseSchemeUser;
		$this->eventNotifier  = $eventNotifier;
	}

	/**
	 * @inheritDoc
	 */
	public function getOrganization(): LoyaltyOrganization
	{
		return $this->organization;
	}

	/**
	 * @inheritDoc
	 */
	public function getLocations(): array
	{
		return $this->locations;
	}

	/**
	 * @return LoyaltyBranding
	 */
	public function getBranding(): LoyaltyBranding
	{
		return $this->branding;
	}

	/**
	 * @return string|null
	 */
	public function getTerms(): ?string
	{
		return $this->terms;
	}

	/**
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
	public function stamp(StampContext $context)
	{
		$this->baseSchemeUser->stamp($context);
	}

	/**
	 * @inheritDoc
	 */
	public function currentCard(): StampCard
	{
		return $this->baseSchemeUser->currentCard();
	}

	/**
	 * @inheritDoc
	 */
	public function redeemableRewards(): array
	{
		return $this->baseSchemeUser->redeemableRewards();
	}

	/**
	 * @return Reward
	 */
	public function getSchemeReward(): Reward
	{
		return $this->baseSchemeUser->getSchemeReward();
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
					$expr->eq('r.profileId', ':profileId'),
					$expr->isNull('r.redeemedAt'),
					$expr->isNull('r.deletedAt')
				)
			)
			->setParameters(
				[
					'schemeId'  => $this->schemeId,
					'id'        => $rewardId,
					'profileId' => $this->baseSchemeUser->getProfileId(),
				]
			)
			->setMaxResults(1)
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
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4
	 */
	public function jsonSerialize()
	{
		return [
			'reward'            => new OutputReward($this->getSchemeReward()),
			'schemeId'          => $this->schemeId->toString(),
			'organization'      => $this->getOrganization(),
			'locations'         => $this->getLocations(),
			'branding'          => $this->getBranding(),
			'terms'             => $this->getTerms(),
			'currentCard'       => $this->currentCard(),
			'redeemableRewards' => $this->redeemableRewards(),
		];
	}
}
