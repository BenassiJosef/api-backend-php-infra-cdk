<?php


namespace App\Package\DataSources;


use App\Models\DataSources\OrganizationRegistration;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Exception;

/**
 * Class OptInService
 * @package App\Package\DataSources
 */
class OptInService
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * OptInService constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}



	/**
	 * @param Organization $organization
	 * @param UserProfile $profile
	 * @return bool
	 */
	public function canSendEmailToUser(Organization $organization, UserProfile $profile): bool
	{
		return $this->canSendEmailToUserWithIds(
			$organization->getId()->toString(),
			$profile->getId()
		);
	}

	/**
	 * @param string $organizationId
	 * @param int $profileId
	 * @return bool
	 */
	public function canSendEmailToUserWithIds(string $organizationId, int $profileId): bool
	{
		$expr = new Expr();
		return $this->query(
			[
				$expr->eq('orgReg.organizationId', ':organizationId'),
				$expr->eq('orgReg.profileId', ':profileId'),
				$expr->isNotNull('orgReg.dataOptInAt'),
				$expr->isNotNull('orgReg.emailOptInAt')
			],
			[
				'organizationId' => $organizationId,
				'profileId'      => $profileId,
			]
		);
	}

	/**
	 * @param string $serial
	 * @param int $profileId
	 * @return bool
	 * @throws Exception
	 */
	public function canSendEmailToUserAtLocationWithIds(string $serial, int $profileId): bool
	{
		$organizationId = $this->locationForSerial($serial)->getOrganizationId()->toString();
		return $this->canSendEmailToUserWithIds($organizationId, $profileId);
	}

	/**
	 * @param Organization $organization
	 * @param UserProfile $userProfile
	 * @return bool
	 */
	public function canSendSMSToUser(Organization $organization, UserProfile $userProfile): bool
	{
		return $this->canSendSMSToUserWithIds(
			$organization->getId()->toString(),
			$userProfile->getId()
		);
	}

	/**
	 * @param string $organizationId
	 * @param int $profileId
	 * @return bool
	 */
	public function canSendSMSToUserWithIds(string $organizationId, int $profileId): bool
	{
		$expr = new Expr();
		return $this->query(
			[
				$expr->eq('orgReg.organizationId', ':organizationId'),
				$expr->eq('orgReg.profileId', ':profileId'),
				$expr->isNotNull('orgReg.dataOptInAt'),
				$expr->isNotNull('orgReg.smsOptInAt')
			],
			[
				'organizationId' => $organizationId,
				'profileId'      => $profileId,
			]
		);
	}

	/**
	 * @param string $serial
	 * @param int $profileId
	 * @return bool
	 * @throws Exception
	 */
	public function dataOptInForLocationWithIds(string $serial, int $profileId): bool
	{
		$organizationId = $this->locationForSerial($serial)->getOrganizationId()->toString();
		return $this->dataOptInWithIds($organizationId, $profileId);
	}

	/**
	 * @param Organization $organization
	 * @param UserProfile $userProfile
	 * @return bool
	 */
	public function dataOptIn(Organization $organization, UserProfile $userProfile): bool
	{
		return $this->dataOptInWithIds(
			$organization->getId()->toString(),
			$userProfile->getId()
		);
	}

	/**
	 * @param string $organizationId
	 * @param int $profileId
	 * @return bool
	 */
	public function dataOptInWithIds(string $organizationId, int $profileId): bool
	{
		$expr = new Expr();
		return $this->query(
			[
				$expr->eq('orgReg.organizationId', ':organizationId'),
				$expr->eq('orgReg.profileId', ':profileId'),
				$expr->isNotNull('orgReg.dataOptInAt'),
				$expr->orX(
					$expr->isNotNull('orgReg.smsOptInAt'),
					$expr->isNotNull('orgReg.emailOptInAt')
				),
			],
			[
				'organizationId' => $organizationId,
				'profileId'      => $profileId,
			]
		);
	}

	/**
	 * @param string $serial
	 * @param int $profileId
	 * @return bool
	 * @throws Exception
	 */
	public function canSendSMSToUserAtLocationWithIds(string $serial, int $profileId): bool
	{
		$organizationId = $this->locationForSerial($serial)->getOrganizationId()->toString();
		return $this->canSendSMSToUserWithIds($organizationId, $profileId);
	}

	private function query(array $predicates, array $parameters): bool
	{
		$qb      = $this->entityManager->createQueryBuilder();
		$expr    = $qb->expr();
		$results = $qb
			->select('orgReg')
			->from(OrganizationRegistration::class, 'orgReg')
			->where(...$predicates)
			->setParameters($parameters)
			->getQuery()
			->getResult();
		return count($results) > 0;
	}

	/**
	 * @param string $serial
	 * @return LocationSettings
	 * @throws Exception
	 */
	private function locationForSerial(string $serial): LocationSettings
	{
		/** @var LocationSettings | null $location */
		$location = $this
			->entityManager
			->getRepository(LocationSettings::class)
			->findOneBy(
				[
					'serial' => $serial,
				]
			);
		if ($location === null) {
			throw new Exception('Location not found');
		}
		return $location;
	}
}
