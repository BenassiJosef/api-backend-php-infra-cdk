<?php

namespace App\Package\Segments\Database\Joins;

use App\Models\BouncedEmails;
use App\Models\DataSources\DataSource;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\GiftCard;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\Organization;
use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidClassException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;

/**
 * Class ClassPool
 * @package App\Package\Segments\Database\Joins
 */
class ClassPool
{
	/**
	 * @return static
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassException
	 * @throws InvalidPropertyException
	 * @throws SegmentException
	 */
	public static function default(): self
	{
		$classPool = new self();


		$classPool
			->getOrAddClass(OrganizationRegistration::class)
			->toMany(
				$classPool
					->getOrAddClass(RegistrationSource::class)
					->toOne(
						$classPool->getOrAddClass(DataSource::class)
					)
			)
			->toOne(
				$classPool->getOrAddClass(UserProfile::class)
					->toOne(
						$classPool->getOrAddClass(BouncedEmails::class, 'email'),
						'email'
					),
				'profileId'
			)
			->toMany(
				$classPool->getOrAddClass(LoyaltyStampCard::class)
			)
			->toMany(
				$classPool->getOrAddClass(UserReview::class)
			)
			->toMany(
				$classPool->getOrAddClass(GiftCard::class)
			)
			->toOne(
				$classPool->getOrAddClass(UserRegistration::class, 'profileId'),
				'profileId'
			);

		return $classPool;
	}

	/**
	 * @param string ...$classNames
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertyException
	 * @throws SegmentException
	 */
	public static function fromClassNames(string ...$classNames): self
	{
		$pool = new self();
		foreach ($classNames as $className) {
			$pool->addClassFromClassName($className);
		}
		return $pool;
	}

	/**
	 * @var JoinClass[] $classes
	 */
	private $classes;

	/**
	 * ClassPool constructor.
	 * @param JoinClass[] $classes
	 */
	public function __construct(JoinClass ...$classes)
	{
		$this->classes = [];
		foreach ($classes as $class) {
			$this->addClass($class);
		}
	}

	/**
	 * @param string $className
	 * @param string $idPropertyName
	 * @return JoinClass
	 * @throws InvalidClassException
	 * @throws InvalidPropertyException
	 * @throws SegmentException
	 */
	public function addClassFromClassName(string $className, string $idPropertyName = 'id'): JoinClass
	{
		$class = JoinClass::fromClassName($className, $idPropertyName);
		$this->addClass(
			$class
		);
		return $class;
	}

	/**
	 * @param JoinClass $class
	 * @return $this
	 */
	public function addClass(JoinClass $class): self
	{
		$this->classes[$class->getClassName()] = $class;
		return $this;
	}

	/**
	 * @param string $className
	 * @return JoinClass
	 * @throws ClassNotInPoolException
	 */
	public function getClass(string $className): JoinClass
	{
		if (!class_exists($className) || !$this->hasClass($className)) {
			throw new ClassNotInPoolException($className);
		}
		return $this->classes[$className];
	}

	/**
	 * @param string $className
	 * @param string $idPropertyName
	 * @return JoinClass
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassException
	 * @throws InvalidPropertyException
	 * @throws SegmentException
	 */
	public function getOrAddClass(string $className, string $idPropertyName = 'id'): JoinClass
	{
		if (!$this->hasClass($className)) {
			$this->addClassFromClassName($className, $idPropertyName);
		}
		return $this->getClass($className);
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	public function hasClass(string $className): bool
	{
		return array_key_exists($className, $this->classes);
	}
}
