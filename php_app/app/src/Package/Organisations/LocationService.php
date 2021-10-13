<?php


namespace App\Package\Organisations;


use App\Models\LocationAccess;
use App\Models\Locations\LocationSettings;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\Role;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Slim\Collection;
use Exception;
use Throwable;

/**
 * Class LocationService
 * @package App\Package\Organisations
 */
class LocationService
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var UserRoleChecker
	 */
	private $userRoleChecker;
	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * LocationService constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager       = $entityManager;
		$this->userRoleChecker     = new UserRoleChecker($entityManager);
		$this->organizationService = new OrganizationService($entityManager);
	}

	/**
	 * Undocumented function
	 *
	 * @param OauthUser $user
	 * @param Organization $organization
	 * @return LocationAccess[]
	 */
	public function getCurrentLocationOrganisationAccess(OauthUser $user, Organization $organization): array
	{
		$organisationSerials = from($organization->getLocations())
			->select(function (LocationSettings $item) {
				return $item->getSerial();
			})->toArray();

		/**
		 * @var LocationAccess[] $currentOrganisationUserAccess
		 */
		$currentOrganisationUserAccess = from($user->getFilteredAccess())
			->where(function (LocationAccess $access) use ($organisationSerials) {
				return in_array($access->getSerial(), $organisationSerials);
			})
			->toArray();

		return array_values($currentOrganisationUserAccess);
	}



	public function updateLocations(LocationAccessChangeRequest $request, Organization $organization)
	{

		$currentAccess = $this->getCurrentLocationOrganisationAccess(
			$request->getSubject(),
			$organization
		);

		$newLocations = from($organization->getLocations())
			->where(function (LocationSettings $item) use ($request) {
				return in_array($item->getSerial(), $request->getSerials());
			})
			->toArray();

		foreach ($currentAccess as $access) {
			$this->entityManager->remove($access);
		}
		$this->entityManager->flush();

		/**
		 * @var LocationAccess[] $locationAccess
		 */
		$locationAccess = [];
		foreach ($newLocations as $location) {
			try {
				$item = new LocationAccess(
					$location,
					$request->getSubject(),
					$request->getRole()
				);
				$this->entityManager->persist($item);
				$locationAccess[] = $item;
			} catch (Throwable $e) {
			}
		}
		$request->getSubject()->setOrganisationAccess($locationAccess);
		$this->entityManager->flush();
		return $request->getSubject();
	}

	/**
	 * @param LocationAccessChangeRequest $request
	 * @throws Throwable
	 */
	public function updateUserLocationAccess(LocationAccessChangeRequest $request)
	{
		$allowableRoles        = $this->allowableRoles();
		$requestedLocations    = $this->locationSettingsForRequest($request);
		$authorisedLocations   = $this->userRoleChecker->locations($request->getAdmin(), $allowableRoles);
		$verificationException = $this->verifyAccess($requestedLocations, $authorisedLocations);
		if ($verificationException !== null) {
			throw $verificationException;
		}
		$subjectCurrentAccess = $this->userRoleChecker->locations($request->getSubject());
		$this->removeLocationAccess($subjectCurrentAccess, $requestedLocations, $request);
		$this->addLocationAccess($subjectCurrentAccess, $requestedLocations, $request);
	}

	/**
	 * @param LocationAccessChangeRequest $request
	 * @return LocationSettings[]
	 */
	public function locationSettingsForRequest(LocationAccessChangeRequest $request)
	{
		$qb   = $this
			->entityManager
			->createQueryBuilder();
		$expr = $qb->expr();
		/** @var Collection | Selectable | LocationSettings[] $locations */
		$locations   = $qb
			->select('ls')
			->from(LocationSettings::class, 'ls')
			->where($expr->in('ls.serial', ':serial'))
			->setParameters(
				[
					"serial" => $request->getSerials(),
				]
			)
			->getQuery()
			->getResult();
		$locationMap = [];
		foreach ($locations as $location) {
			$locationMap[$location->getSerial()] = $location;
		}
		return $locationMap;
	}

	/**
	 * @param LocationSettings $locationSettings
	 * @param Role[] $roles
	 * @return OauthUser[]
	 */
	public function whoCanAccessLocation(LocationSettings $locationSettings, array $roles = []): array
	{
		$users  = $this->whoCanDirectlyAccessLocation($locationSettings, $roles);
		$parent = $locationSettings->getOrganization();
		while ($parent !== null) {
			$orgUsers = $this->organizationService->whoCanAccessOrganization($parent);
			foreach ($orgUsers as $id => $user) {
				$users[$id] = $user;
			}
			$parent = $parent->getParent();
		}
		return $users;
	}

	/**
	 * @param string $serial
	 * @return LocationSettings|null
	 */
	public function getLocationBySerial(string $serial)
	{
		/** @var LocationSettings $locationSettings */
		$locationSettings = $this
			->entityManager
			->getRepository(LocationSettings::class)
			->findOneBy(
				[
					'serial' => $serial,
				]
			);
		return $locationSettings;
	}

	/**
	 * @param string[] $serials
	 * @return LocationSettings[]
	 */
	public function getLocationsBySerial(array $serials): array
	{
		/** @var LocationSettings[] $locationSettings */
		$locationSettings = $this
			->entityManager
			->getRepository(LocationSettings::class)
			->findBy(
				[
					'serial' => $serials,
				]
			);
		return $locationSettings;
	}

	private function whoCanDirectlyAccessLocation(LocationSettings $locationSettings, array $roles = []): array
	{
		$criteria = [
			'serial' => $locationSettings->getSerial(),
		];

		if (count($roles) > 0) {
			$roleIds = [];
			foreach ($roles as $role) {
				$roleIds[] = $role->getId();
			}
			$criteria['roleId'] = $roleIds;
		}
		/** @var LocationAccess[] $accesses */
		$accesses = $this
			->entityManager
			->getRepository(LocationAccess::class)
			->findBy($criteria);
		$users    = [];
		foreach ($accesses as $access) {
			$user                   = $access->getUser();
			$users[$user->getUid()] = $user;
		}
		return $users;
	}

	/**
	 * @return Role[]
	 */
	private function allowableRoles(): array
	{
		return $this
			->entityManager
			->getRepository(Role::class)
			->findBy(
				[
					'legacyId' => [
						Role::LegacyAdmin,
						Role::LegacyReseller,
						Role::LegacySuperAdmin,
					],
				]
			);
	}

	/**
	 * @param LocationSettings[] $currentLocations
	 * @param LocationSettings[] $requestedLocations
	 * @param LocationAccessChangeRequest $accessRequest
	 * @throws ORMInvalidArgumentException
	 * @throws ORMException
	 */
	private function addLocationAccess(
		array $currentLocations,
		array $requestedLocations,
		LocationAccessChangeRequest $accessRequest
	) {
		foreach ($requestedLocations as $location) {
			if (array_key_exists($location->getSerial(), $currentLocations)) {

				continue;
			}
			$locationAccess = new LocationAccess(
				$location,
				$accessRequest->getSubject(),
				$accessRequest->getRole()
			);
			$accessRequest
				->getSubject()
				->getLocationAccess()
				->add($locationAccess);
		}
	}

	/**
	 * @param LocationSettings[] $currentLocations
	 * @param LocationSettings[] $requestedLocations
	 * @param LocationAccessChangeRequest $accessRequest
	 * @throws ORMInvalidArgumentException
	 * @throws ORMException
	 */
	private function removeLocationAccess(
		array $currentLocations,
		array $requestedLocations,
		LocationAccessChangeRequest $accessRequest
	) {
		$locationAccessRepository = $this
			->entityManager
			->getRepository(LocationAccess::class);
		foreach ($currentLocations as $location) {
			$serial = $location->getSerial();
			if (array_key_exists($serial, $requestedLocations)) {
				continue;
			}
			$criteria       = [
				'serial' => $serial,
				'userId' => $accessRequest->getSubject()->getUid(),
				'roleId' => $accessRequest->getRole()->getId(),
			];
			$locationAccess = $locationAccessRepository->findOneBy($criteria);
			if (!is_null($locationAccess)) {
				$this
					->entityManager
					->remove($locationAccess);
			}
		}
	}

	/**
	 * @param LocationSettings[] $requestedLocations
	 * @param LocationSettings[] $authorisedLocations
	 * @return Throwable
	 */
	private function verifyAccess(
		array $requestedLocations,
		array $authorisedLocations
	): ?Throwable {
		$unauthorizedLocations = [];
		foreach ($requestedLocations as $requestedLocation) {
			$serial = $requestedLocation->getSerial();
			if (!array_key_exists($serial, $authorisedLocations)) {
				$unauthorizedLocations[] = $serial;
			}
		}
		if (count($unauthorizedLocations) !== 0) {
			$locationString = implode(',', $unauthorizedLocations);
			return new Exception("User has no access to the following location(s) ($locationString)");
		}

		return null;
	}
}
