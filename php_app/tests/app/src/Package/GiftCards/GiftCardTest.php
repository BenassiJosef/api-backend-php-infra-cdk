<?php

namespace StampedeTests\app\src\Package\GiftCards;

use App\Models\Billing\Organisation\Subscriptions;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\GiftCardSettings;
use App\Models\GiftCard;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\StripeConnect;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class GiftCardTest extends TestCase
{
	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var OauthUser
	 */
	private $user;

	/**
	 * @var Organization
	 */
	private $org;
	/**
	 * @var UserProfile
	 */
	private $profile;

	/**
	 * @var OrganizationRegistration
	 */
	private $organisationRegistration;

	public function setUp(): void
	{
		$this->em = DoctrineHelpers::createEntityManager();
		$this->em->beginTransaction();
		$rootOrg       = EntityHelpers::createRootOrg($this->em);
		$this->user    = EntityHelpers::createOauthUser($this->em, "foo@bar.com", "", "Bobs burgers", "");
		$this->org     = EntityHelpers::createOrganisation($this->em, "My ORG", $this->user);
		$this->profile = EntityHelpers::createUser($this->em, "baz@qux.com", "01234 567890", "M", 1, 2);

		$this->organisationRegistration = EntityHelpers::createOrganizationRegistration($this->em, $this->org, $this->profile);
		$stripeConnect = new StripeConnect(
			$this->org,
			"Bearer",
			"foo",
			"bar",
			"ALL",
			true,
			"baz",
			"qux"
		);
		$this->em->persist($stripeConnect);
		$this->em->flush();
	}

	/**
	 * @throws Exception
	 */
	public function testCanCreateSettingsAndGiftCard()
	{
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->em
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"organizationId" => $this->org->getId(),
				]
			);
		$settings      = new GiftCardSettings($stripeConnect, "Help Bobs Burgers", "Help us by buying a gift card");
		$this->em->persist($stripeConnect);
		$this->em->flush();
		$giftCard = new GiftCard($settings, $this->organisationRegistration, 1000);
		$giftCard->activate("123456");
		$this->em->persist($giftCard);
		$this->em->flush();

		$this->em->clear();

		/** @var GiftCard $giftCard */
		$giftCard = $this->em->getRepository(GiftCard::class)->findOneBy(["transactionId" => "123456"]);
		self::assertNotNull($giftCard);
		echo $giftCard->qrCodeURI();
	}

	public function testEnterprisePlanHasLowFee()
	{
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->em
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"organizationId" => $this->org->getId(),
				]
			);
		$subscription = new Subscriptions(
			$this->org,
			'fsgsgsgrsfg',
			[],
			50000,
			10,
			Subscriptions::PLAN_ENTERPRISE,
			'GBP',
			'active',
			true
		);
		$this->em->persist($subscription);
		$settings      = new GiftCardSettings($stripeConnect, "Help Bobs Burgers", "Help us by buying a gift card");
		$this->em->persist($stripeConnect);
		$this->em->flush();
		$giftCard = new GiftCard($settings, $this->organisationRegistration, 1000);
		$giftCard->activate("1234567");
		$this->em->persist($giftCard);
		$this->em->flush();

		$this->em->clear();

		/** @var GiftCard $giftCard */
		$giftCard = $this->em->getRepository(GiftCard::class)->findOneBy(["transactionId" => "1234567"]);

		self::assertEquals(6, $giftCard->fee());
	}

	public function testLegacyAllPlanHasHighFee()
	{
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->em
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"organizationId" => $this->org->getId(),
				]
			);
		$subscription = new Subscriptions(
			$this->org,
			'fsgsgsgrsfg',
			[],
			50000,
			10,
			Subscriptions::PLAN_LEGACY_ALL,
			'GBP',
			'active',
			true
		);
		$this->em->persist($subscription);
		$settings      = new GiftCardSettings($stripeConnect, "Help Bobs Burgers", "Help us by buying a gift card");
		$this->em->persist($stripeConnect);
		$this->em->flush();
		$giftCard = new GiftCard($settings, $this->organisationRegistration, 1000);
		$giftCard->activate("1234567");
		$this->em->persist($giftCard);
		$this->em->flush();

		$this->em->clear();

		/** @var GiftCard $giftCard */
		$giftCard = $this->em->getRepository(GiftCard::class)->findOneBy(["transactionId" => "1234567"]);

		self::assertEquals(46, $giftCard->fee());
	}

	public function testFreePlanHasHighFee()
	{
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->em
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"organizationId" => $this->org->getId(),
				]
			);
		$subscription = new Subscriptions(
			$this->org,
			'fsgsgsgrsfg',
			[],
			50000,
			10,
			Subscriptions::PLAN_FREE,
			'GBP',
			'active',
			true
		);
		$this->em->persist($subscription);
		$settings      = new GiftCardSettings($stripeConnect, "Help Bobs Burgers", "Help us by buying a gift card");
		$this->em->persist($stripeConnect);
		$this->em->flush();
		$giftCard = new GiftCard($settings, $this->organisationRegistration, 1000);
		$giftCard->activate("1234567");
		$this->em->persist($giftCard);
		$this->em->flush();

		$this->em->clear();

		/** @var GiftCard $giftCard */
		$giftCard = $this->em->getRepository(GiftCard::class)->findOneBy(["transactionId" => "1234567"]);

		self::assertEquals(46, $giftCard->fee());
	}

	public function testNoSubscriptionHasLowFee()
	{
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->em
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"organizationId" => $this->org->getId(),
				]
			);
		$settings      = new GiftCardSettings($stripeConnect, "Help Bobs Burgers", "Help us by buying a gift card");
		$this->em->persist($stripeConnect);
		$this->em->flush();
		$giftCard = new GiftCard($settings, $this->organisationRegistration, 1000);
		$giftCard->activate("1234567");
		$this->em->persist($giftCard);
		$this->em->flush();

		$this->em->clear();

		/** @var GiftCard $giftCard */
		$giftCard = $this->em->getRepository(GiftCard::class)->findOneBy(["transactionId" => "1234567"]);

		self::assertEquals(6, $giftCard->fee());
	}

	public function tearDown(): void
	{
		$this->em->rollback();
	}
}
