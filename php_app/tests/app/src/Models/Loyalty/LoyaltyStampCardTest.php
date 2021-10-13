<?php

namespace StampedeTests\app\src\Models\Loyalty;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NotActiveException;
use App\Models\Loyalty\Exceptions\NotEnoughStampsException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Loyalty\Stamps\StampContext;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;
use Throwable;

class LoyaltyStampCardTest extends TestCase
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	/**
	 * @var Organization|null
	 */
	private $organization;
	/**
	 * @var UserProfile|null
	 */
	private $userProfile;

	/**
	 * @var OrganizationRegistration|null
	 */
	private $organizationRegistration;

	/**
	 * @var OauthUser $stamper
	 */
	private $stamper;

	protected function setUp(): void
	{
		$this->entityManager = DoctrineHelpers::createEntityManager();
		$this->entityManager->beginTransaction();

		$this->organization = $this
			->entityManager
			->getRepository(Organization::class)
			->findOneBy(
				[
					'name' => 'Some Company Ltd',
				]
			);

		$this->userProfile = $this
			->entityManager
			->getRepository(UserProfile::class)
			->findOneBy(
				[
					'email' => 'alistair.judson@stampede.ai'
				]
			);

		$this->organizationRegistration = $this
			->entityManager
			->getRepository(OrganizationRegistration::class)
			->findOneBy([
				'profileId' => $this->userProfile->getId()
			]);

		$this->stamper = $this
			->entityManager
			->getRepository(OauthUser::class)
			->findOneBy(
				[
					'email' => 'some.admin@stampede.ai',
				]
			);
	}

	protected function tearDown(): void
	{
		$this->entityManager->rollback();
	}

	public function testFillCardAndRedeem()
	{
		$context = StampContext::organizationStamp($this->stamper);
		$reward  = LoyaltyReward::newItemReward(
			$this->organization,
			'Coffee'
		);

		$scheme = new LoyaltyStampScheme(
			$this->organization,
			$reward
		);
		$card   = new LoyaltyStampCard(
			$scheme,
			$this->organizationRegistration
		);
		try {
			$card->redeem();
		} catch (Throwable $throwable) {
			self::assertInstanceOf(NotActiveException::class, $throwable);
		}
		// Check that a new empty card isn't active
		self::assertFalse($card->isActivated());

		// Check that a new empty card isn't full...
		self::assertFalse($card->isFull());

		// Add three stamps
		for ($i = 0; $i < 3; $i++) {
			$card->stamp($context, 1);
		}

		// Assert that the card is active
		self::assertTrue($card->isActivated());

		// Try to activate it (adding stamps has activated it, so it should fail)
		try {
			$card->activate();
		} catch (Throwable $throwable) {
			self::assertInstanceOf(AlreadyActivatedException::class, $throwable);
		}

		// Try to redeem a non-full card (it should fail)
		try {
			$card->redeem();
		} catch (Throwable $throwable) {
			self::assertInstanceOf(NotEnoughStampsException::class, $throwable);
		}

		// Try to add 4 stamps in a 1ner (the card has 3 stamps already, with a capacity of 6, so this should fail)
		try {
			$card->stamp($context, 4);
		} catch (Throwable $throwable) {
			self::assertInstanceOf(OverstampedCardException::class, $throwable);
		}

		// Stamp three more stamps to fill up the card
		for ($i = 0; $i < 3; $i++) {
			$card->stamp($context, 1);
		}

		// Check that the card is full
		self::assertTrue($card->isFull());


		try {
			// Try stamping a full card (should fail)
			$card->stamp($context);
		} catch (Throwable $throwable) {
			self::assertInstanceOf(FullCardException::class, $throwable);
		}

		// Check that the reward returned when redeeming is called "Coffee"
		self::assertEquals($card->redeem()->getName(), 'Coffee');


		try {
			// Redeeming an already redeemed card should fail
			$card->redeem();
		} catch (Throwable $throwable) {
			self::assertInstanceOf(AlreadyRedeemedException::class, $throwable);
		}


		try {
			// Stamping an already redeemed card should fail
			$card->stamp($context);
		} catch (Throwable $throwable) {
			self::assertInstanceOf(AlreadyRedeemedException::class, $throwable);
		}

		// Get the types of all the events
		$gotEventTypes = from($card->getEvents())
			->toValues()
			->select(
				function (LoyaltyStampCardEvent $event): string {
					return $event->getType();
				}
			)
			->toArray();

		$expectedEventTypes = [
			LoyaltyStampCardEvent::TYPE_CREATE,
			LoyaltyStampCardEvent::TYPE_ACTIVATE,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_STAMP,
			LoyaltyStampCardEvent::TYPE_FILLED,
			LoyaltyStampCardEvent::TYPE_REDEEM,
		];

		// Compare to the expected event types.
		self::assertEquals($expectedEventTypes, $gotEventTypes);
	}

	public function testJsonSerialize()
	{
		$reward = LoyaltyReward::newItemReward(
			$this->organization,
			'Coffee'
		);

		$scheme   = new LoyaltyStampScheme(
			$this->organization,
			$reward
		);
		$card     = new LoyaltyStampCard(
			$scheme,
			$this->organizationRegistration,
			2
		);
		$jsonData = json_decode(json_encode($card->jsonSerialize(), JSON_PRETTY_PRINT), JSON_OBJECT_AS_ARRAY);
		self::assertSameSize($card->getEvents(), $jsonData['events']);
		$this->entityManager->persist($card);
		$this->entityManager->flush();
	}
}
