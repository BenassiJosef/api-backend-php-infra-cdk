<?php

namespace StampedeTests\app\src\Package\Segments\Database\Joins;

use App\Models\DataSources\DataSource;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\GiftCard;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Reviews\UserReview;
use App\Models\UserProfile;
use App\Package\Segments\Database\Joins\ClassPool;
use App\Package\Segments\Database\Joins\JoinClass;
use App\Package\Segments\Database\Joins\JoinOn;
use PHPUnit\Framework\TestCase;

class JoinClassTest extends TestCase
{

	public function testJoinTypeToClass()
	{
		$classPool = ClassPool::default();

		self::assertEquals(
			JoinOn::TYPE_TO_MANY,
			$classPool
				->getClass(OrganizationRegistration::class)
				->joinTypeToClass($classPool->getClass(DataSource::class))
		);
	}

	public function testToOne()
	{
		$classPool = new ClassPool();
		$classPool->addClassFromClassName(OrganizationRegistration::class);
		$classPool->addClassFromClassName(UserProfile::class);
		$classPool->addClassFromClassName(RegistrationSource::class);
		$classPool->addClassFromClassName(DataSource::class);
		$classPool->addClassFromClassName(UserReview::class);
		$classPool->addClassFromClassName(GiftCard::class);
		$classPool->addClassFromClassName(LoyaltyStampCard::class);

		$joins = $classPool
			->getClass(OrganizationRegistration::class)
			->toOne(
				$classPool->getClass(UserProfile::class),
				'profileId'
			)
			->getJoins();
		/** @var JoinOn $joinOut */
		$joinOut = from($joins)
			->single(
				function (JoinOn $joinOn): bool {
					return $joinOn->getFromClass()->getClassName() === OrganizationRegistration::class;
				}
			);

		self::assertEquals(JoinOn::TYPE_TO_ONE, $joinOut->getType());
	}

	public function testToMany()
	{
		$classPool = new ClassPool();
		$classPool->addClassFromClassName(OrganizationRegistration::class);
		$classPool->addClassFromClassName(UserProfile::class);
		$classPool->addClassFromClassName(RegistrationSource::class);
		$classPool->addClassFromClassName(DataSource::class);

		$joins = $classPool
			->getClass(UserProfile::class)
			->toMany(
				$classPool->getClass(OrganizationRegistration::class),
				'profileId'
			)
			->getJoins();
		/** @var JoinOn $joinOut */
		$joinOut = from($joins)
			->single(
				function (JoinOn $joinOn): bool {
					return $joinOn->getFromClass()->getClassName() === UserProfile::class;
				}
			);

		self::assertEquals(JoinOn::TYPE_TO_MANY, $joinOut->getType());
	}
}
