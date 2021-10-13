<?php

namespace StampedeTests\app\src\Package\Segments\Database\Aliases;

use App\Models\DataSources\DataSource;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\GiftCard;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Reviews\UserReview;
use App\Package\Segments\Database\Aliases\TableAliasProvider;
use PHPUnit\Framework\TestCase;

class TableAliasProviderTest extends TestCase
{

	public function testSubTableAliasProvider()
	{
		$tableAliasProvider = new TableAliasProvider();

		// When requesting an alias for a table, the alias returned by a TableAliasProvider instance must be consistent
		self::assertEquals('or1', $tableAliasProvider->alias(OrganizationRegistration::class));
		self::assertEquals('or1', $tableAliasProvider->alias(OrganizationRegistration::class));
		self::assertEquals('rs', $tableAliasProvider->alias(RegistrationSource::class));
		self::assertEquals('ur', $tableAliasProvider->alias(UserReview::class));
		self::assertEquals('gc', $tableAliasProvider->alias(GiftCard::class));
		self::assertEquals('lsc', $tableAliasProvider->alias(LoyaltyStampCard::class));


		// When requesting an alias from a sub TableAliasProvider instance, if
		// it's in the "Static" list, it must delegate it's aliasing to the TableAliasProvider that created it.
		$sub = $tableAliasProvider
			->subTableAliasProvider(OrganizationRegistration::class);
		self::assertEquals('or1', $sub->alias(OrganizationRegistration::class));
		self::assertEquals('rs1', $sub->alias(RegistrationSource::class));
		self::assertEquals('rs1', $sub->alias(RegistrationSource::class));


		// A second sub TableAliasProvider from the root, should also delegate, but issue a new alias
		$sub2 = $tableAliasProvider
			->subTableAliasProvider(OrganizationRegistration::class);
		self::assertEquals('or1', $sub2->alias(OrganizationRegistration::class));
		self::assertEquals('rs2', $sub2->alias(RegistrationSource::class));
		self::assertEquals('rs2', $sub2->alias(RegistrationSource::class));



		// "Static" ClassNames are passed down to children from
		// their parent, so classes should remain static all the way down
		$sub3 = $sub->subTableAliasProvider(RegistrationSource::class);
		self::assertEquals('or1', $sub3->alias(OrganizationRegistration::class));
		self::assertEquals('rs1', $sub3->alias(RegistrationSource::class));
		self::assertEquals('ds', $sub3->alias(DataSource::class));
		self::assertEquals('ds', $sub3->alias(DataSource::class));
	}
}
