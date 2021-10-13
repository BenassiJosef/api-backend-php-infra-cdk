<?php


namespace App\Package\Organisations;


use App\Models\Locations\LocationSettings;
use App\Models\Members\CustomerPricing;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Models\Role;
use App\Models\UidToUUID;
use App\Package\Database\BaseStatement;
use App\Package\Database\FirstColumnFetcher;
use App\Package\Database\RawStatementExecutor;
use Aws\Organizations\Exception\OrganizationsException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use phpDocumentor\Reflection\Location;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Exception;

/**
 * Class OrganisationAccessDeniedException
 * @package App\Package\Organisations
 */
class OrganisationAccessDeniedException extends Exception
{
}

/**
 * Class OrganisationNotFoundException
 * @package App\Package\Organisations
 */
class OrganisationNotFoundException extends Exception
{
}

/**
 * Class OrganizationService
 * @package App\Package\Organisations
 */
class OrganizationService
{
	/**
	 * @var ObjectRepository|EntityRepository $organisationRepository
	 */
	private $organisationRepository;

	/**
	 * @var ObjectRepository|EntityRepository $userRepository
	 */
	private $userRepository;

	/**
	 * @var ObjectRepository|EntityRepository $userRepository
	 */
	private $roleRepository;

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var ObjectRepository|EntityRepository $organisationAccessRepository
	 */
	private $organisationAccessRepository;
	/**
	 * @var UserRoleChecker
	 */
	private $userRoleChecker;

	/**
	 * @var FirstColumnFetcher $database
	 */
	private $database;

	/**
	 * OrganizationService constructor.
	 * @param EntityManager $entityManager
	 * @param UserRoleChecker $userRoleChecker
	 */
	public function __construct(EntityManager $entityManager, UserRoleChecker $userRoleChecker = null)
	{
		$this->entityManager                = $entityManager;
		$this->database                     = new RawStatementExecutor($entityManager);
		$this->organisationRepository       = $entityManager->getRepository(Organization::class);
		$this->organisationAccessRepository = $entityManager->getRepository(OrganizationAccess::class);
		$this->userRepository               = $entityManager->getRepository(OauthUser::class);
		$this->roleRepository               = $entityManager->getRepository(Role::class);
		if ($userRoleChecker === null) {
			$userRoleChecker = new UserRoleChecker($this->entityManager);
		}
		$this->userRoleChecker = $userRoleChecker;
	}

	/**
	 * @param string $id
	 * @return Organization|null
	 */
	public function getOrganisationById(string $id): ?Organization
	{
		/** @var Organization $org */
		$org = $this->entityManager->getRepository(Organization::class)->find($id);

		return $org;
	}

	/**
	 * @param UuidInterface $ownerId
	 * @param string|null $name
	 * @return Organization
	 * @throws ORMException
	 */
	public function getOrCreateOrganizationForOwnerId(UuidInterface $ownerId, ?string $name = null): Organization
	{
		return $this->getOrCreateOrganizationForOwnerIdString($ownerId->toString(), $name);
	}

	/**
	 * @param string $ownerId
	 * @param string|null $name
	 * @return Organization
	 * @throws ORMException
	 */
	public function getOrCreateOrganizationForOwnerIdString(string $ownerId, ?string $name): Organization
	{
		if (strlen($ownerId) !== 36) {
			/** @var UidToUUID $mapping */
			$mapping = $this->entityManager->getRepository(UidToUUID::class)->find($ownerId);
			$ownerId = $mapping->getCorrect();
		}

		/** @var OauthUser $owner */
		$owner = $this->userRepository->find($ownerId);
		if ($owner === null) {
			throw new Exception("could not find {$ownerId}");
		}
		$organization = $this->getOrganizationForOwner($owner);
		if ($organization !== null) {
			return $organization;
		}

		return $this->createOrganizationForOwner($owner, $name);
	}

	/**
	 * @param OauthUser $owner
	 * @return Organization | null
	 */
	public function getOrganizationForOwner(OauthUser $owner): ?Organization
	{
		/** @var Organization | null $organization */
		$organization = $this->organisationRepository->findOneBy(
			[
				'ownerId' => $owner->getUid(),
			]
		);

		return $organization;
	}

	/**
	 * @param string $uid
	 * @return Organization|null
	 */
	public function getOrganizationForOwnerId(string $uid)
	{
		/** @var Organization | null $organization */
		$organization = $this->organisationRepository->findOneBy(
			[
				'ownerId' => $uid,
			]
		);

		return $organization;
	}

	public function getOrganizationsForOwnerWithBilling(OauthUser $user): array
	{
		$qb   = $this->entityManager->createQueryBuilder();
		$expr = $qb->expr();

		return $qb
			->select('o')
			->from(Organization::class, 'o')
			->where($expr->eq("o.ownerId", $user->getUid()))
			->andWhere($expr->isNotNull('o.chargebeeCustomerId'))
			->getQuery()->getResult();
	}

	/**
	 * @return Organization
	 */
	public function getRootOrganisation(): Organization
	{
		/** @var Organization $org */
		$org = $this->entityManager->getRepository(Organization::class)->findOneBy(
			[
				'type' => Organization::RootType,
			]
		);

		return $org;
	}

	/**]
	 * @param OauthUser $owner
	 * @param string|null $name
	 * @param Organization|null $parent
	 * @param string|null $chargeBeeCustomerId
	 * @return Organization
	 * @throws ORMException
	 * @throws Exception
	 */
	public function createOrganizationForOwner(OauthUser $owner, string $name = null, Organization $parent = null, ?string $chargeBeeCustomerId = null): Organization
	{
		$organization = new Organization(
			is_null($name) ? $owner->getCompany() ?? $owner->getFirst() . "'s Organization'" : $name,
			$owner,
			$parent ?? $this->getRootOrganisation(),
			$chargeBeeCustomerId
		);
		$this->entityManager->persist($organization);
		// create admin access for the owner
		$access = new OrganizationAccess($organization, $owner, $this->getAdminRole());
		$this->entityManager->persist($access);
		// create the pricing
		$newCustomerPricing = new CustomerPricing($organization);
		$this->entityManager->persist($newCustomerPricing);
		return $organization;
	}

	/**
	 * @return Role
	 */
	private function getAdminRole(): Role
	{
		/** @var Role $role */
		$role = $this->roleRepository->findOneBy(
			[
				'legacyId' => Role::LegacyAdmin,
			]
		);

		return $role;
	}

	/**
	 * @param Organization $org
	 * @param OauthUser $user
	 * @param Role[] $roles
	 * @return bool
	 */
	public function hasAccess(Organization $org, OauthUser $user, array $roles): bool
	{
		$roleIds = [];
		foreach ($roles as $role) {
			$roleIds[] = $role->getLegacyId();
		}

		return $this->userRoleChecker->hasAccessToOrganizationAsRole($user, $org->getId()->toString(), $roleIds);
	}

	/**
	 * @param OauthUser $user
	 * @param string $orgId
	 * @param string $name
	 * @return Organization
	 * @throws ORMException
	 * @throws OrganisationAccessDeniedException
	 *
	 */
	public function updateName(OauthUser $user, string $orgId, string $name): Organization
	{
		/** @var Organization $org */
		$org = $this->organisationRepository->find($orgId);
		if (is_null($org)) {
			throw new OrganisationNotFoundException('organization not found');
		}
		$adminRole = $this->getAdminRole();
		if ($this->hasAccess($org, $user, [$adminRole])) {
			$org->setName($name);
			$this->entityManager->persist($org);

			return $org;
		} else {
			throw new OrganisationAccessDeniedException("Only and admin can change an organisation name");
		}
	}

	/**
	 * @param OauthUser $user
	 * @param UuidInterface $orgId
	 * @param Role[] $roles
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 */
	public function getOrganisation(OauthUser $user, UuidInterface $orgId, Role ...$roles): Organization
	{
		/** @var Organization $org */
		$org = $this->organisationRepository->find($orgId);
		if (is_null($org)) {
			throw new OrganisationNotFoundException();
		}
		if ($this->hasAccess($org, $user, $roles)) {

			return $org->getFilteredLocations($this->userRoleChecker->locationSerials($user));
		} else {
			throw new OrganisationAccessDeniedException("You do not have access to this organisation");
		}
	}

	/**
	 * Change the parent of an organisation
	 * @param OauthUser $user
	 * @param UuidInterface $childId
	 * @param UuidInterface $newParentId
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 */
	public function updateParent(OauthUser $user, UuidInterface $childId, UuidInterface $newParentId): Organization
	{
		$child     = $this->getOrganisation($user, $childId, $this->getAdminRole());
		$newParent = $this->getOrganisation($user, $newParentId, $this->getAdminRole());
		$newParent->addChild($child);

		return $newParent;
	}

	/**
	 * Add a child to an organisation
	 * @param OauthUser $user
	 * @param UuidInterface $parentId
	 * @param UuidInterface $childId
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 * @throws ORMException
	 */
	public function addChild(OauthUser $user, UuidInterface $parentId, UuidInterface $childId): Organization
	{
		$parent = $this->getOrganisation($user, $parentId, $this->getAdminRole());
		$child  = $this->getOrganisation($user, $childId, $this->getAdminRole());
		$parent->addChild($child);
		$this->entityManager->persist($parent);

		return $parent;
	}

	/**
	 * @param OauthUser $owner
	 * @param UuidInterface $parentId
	 * @param string $name
	 * @return Organization
	 * @throws ORMException
	 * @throws OrganisationAccessDeniedException
	 * @throws OrganisationNotFoundException
	 */
	public function createChild(OauthUser $owner, UuidInterface $parentId, string $name): Organization
	{
		$parent = $this->getOrganisation($owner, $parentId, $this->getAdminRole());
		$child  = $this->createOrganizationForOwner($owner, $name);
		$parent->addChild($child);
		$this->entityManager->persist($parent);

		return $child;
	}

	/**
	 * Remove a child from an organisation
	 * @param OauthUser $user
	 * @param UuidInterface $childId
	 * @param UuidInterface $parentId
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 * @throws ORMException
	 */
	public function removeChild(OauthUser $user, UuidInterface $childId, UuidInterface $parentId): Organization
	{
		/** @var Organization $parent */
		$parent = $this->getOrganisation($user, $parentId, $this->getAdminRole());
		$child  = $this->getOrganisation($user, $childId, $this->getAdminRole());
		$parent->removeChild($child);
		$this->entityManager->persist($parent);

		return $parent;
	}

	/**
	 * Set the children of an organisation
	 * @param OauthUser $user
	 * @param UuidInterface $orgId
	 * @param Organization[] $childIds
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 * @throws ORMException
	 */
	public function setChildren(OauthUser $user, UuidInterface $orgId, array $childIds): Organization
	{
		/** @var Organization[] $children */
		$children = [];
		foreach ($childIds as $childId) {
			$children[] = $this->getOrganisation($user, $childId, $this->getAdminRole());
		}
		$parent = $this->getOrganisation($user, $orgId, $this->getAdminRole());
		$parent->setChildren($children);
		$this->entityManager->persist($parent);

		return $parent;
	}

	/**
	 * Get the child organisations
	 * @param OauthUser $user
	 * @param UuidInterface $orgId
	 * @return Collection
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 */
	public function getChildren(OauthUser $user, UuidInterface $orgId): Collection
	{
		$org = $this->getOrganisation($user, $orgId, $this->getAdminRole());

		return $org->getChildren();
	}

	/**
	 * Add a location to an organisation
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @param string $serial
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 * @throws ORMException
	 */
	public function addLocation(OauthUser $oauthUser, UuidInterface $orgId, string $serial): Organization
	{
		$org = $this->getOrganisation($oauthUser, $orgId, $this->getAdminRole());
		/** @var LocationSettings $location */
		$location = $this->entityManager->getRepository(LocationSettings::class)->findOneBy(["serial" => $serial]);
		if (!$this->hasAccess($location->getOrganization(), $oauthUser, [$this->getAdminRole()])) {
			throw new OrganisationAccessDeniedException("You do not have access to this location");
		}
		$org->addLocation($location);
		$this->entityManager->persist($org);

		return $org;
	}

	/**
	 * Add a set of locations to an organisation
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @param array $serials
	 * @return Organization
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 * @throws ORMException
	 */
	public function addLocations(OauthUser $oauthUser, UuidInterface $orgId, array $serials): Organization
	{
		$org = $this->getOrganisation($oauthUser, $orgId, $this->getAdminRole());
		/** @var LocationSettings[] $locations */
		$locations = $this->entityManager->getRepository(LocationSettings::class)->findBy(['serial' => $serials]);
		foreach ($locations as $location) {
			if (!$this->hasAccess($location->getOrganization(), $oauthUser, [$this->getAdminRole()])) {
				throw new OrganisationAccessDeniedException("You do not have access to this location");
			}
			$org->addLocation($location);
		}
		$this->entityManager->persist($org);

		return $org;
	}

	/**
	 * Get the location for an organisation
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @return LocationSettings[]|Collection|Selectable
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 */
	public function getLocations(OauthUser $oauthUser, UuidInterface $orgId)
	{
		$org = $this->getOrganisation($oauthUser, $orgId, $this->getAdminRole());

		return $org->getLocations();
	}

	/**
	 * @param UuidInterface $parentId
	 * @param string $userId
	 * @param UuidInterface[] $roleIds
	 * @return UuidInterface[]
	 */
	public function getOrgIdsDeep(UuidInterface $parentId, string $userId, array $roleIds)
	{

		$orgIds   = [];
		$children = $this->entityManager->createQueryBuilder()
			->select('o.id')
			->from(Organization::class, 'o')
			->leftJoin(OrganizationAccess::class, 'oa', 'with', 'oa.organizationId = o.id')
			->where('(oa.roleId in (:roleIds) and oa.userId = :userId) or o.ownerId = :userId')
			->andWhere('o.parentId = :parentId')
			->setParameter('roleIds', $roleIds)
			->setParameter('userId', $userId)
			->setParameter('parentId', $parentId)
			->getQuery()
			->getArrayResult();
		foreach ($children as $child) {
			$childId     = $child['id'];
			$subChildren = $this->getOrgIdsDeep($childId, $userId, $roleIds);
			foreach ($subChildren as $subChild) {
				$orgIds[] = $subChild;
			}
		}

		return $orgIds;
	}

	/**
	 * @param Organization $organization
	 * @param Role[] $roles
	 * @return OauthUser[]
	 */
	public function whoCanAccessOrganization(Organization $organization, array $roles = []): array
	{
		$access = [];
		while ($organization !== null) {
			$directAccess = $this->whoCanDirectlyAccessOrganization($organization, $roles);
			foreach ($directAccess as $id => $user) {
				$access[$id] = $user;
			}
			$organization = $organization->getParent();
		}

		return $access;
	}

	/**
	 * @param Organization $organization
	 * @param Role[] $roles
	 * @return OauthUser[]
	 */
	private function whoCanDirectlyAccessOrganization(Organization $organization, array $roles = []): array
	{
		$criteria = [
			'organizationId' => $organization->getId(),
		];

		if (count($roles) > 0) {
			$roleIds = [];
			foreach ($roles as $role) {
				$roleIds[] = $role->getId();
			}
			$criteria['roleId'] = $roleIds;
		}

		/** @var OrganizationAccess[] $accesses */
		$accesses = $this
			->entityManager
			->getRepository(OrganizationAccess::class)
			->findBy($criteria);
		$users    = [];
		foreach ($accesses as $access) {
			$user                   = $access->getUser();
			$users[$user->getUid()] = $user;
		}

		return $users;
	}

	/**
	 * Get the users on an organisation
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @return OrganizationAccess[]|ArrayCollection|Collection|Selectable
	 * @throws OrganisationNotFoundException
	 * @throws OrganisationAccessDeniedException
	 */
	public function getUsers(
		OauthUser $oauthUser,
		UuidInterface $orgId
	) {
		$adminRole = $this->getAdminRole();
		$org       = $this->getOrganisation($oauthUser, $orgId, $this->getAdminRole());
		$orgIds    = [$org->getId()];

		return $this->entityManager->createQueryBuilder()
			->select('oa')
			->from(OrganizationAccess::class, 'oa')
			->where('oa.organizationId in (:orgIds)')
			->setParameter('orgIds', $orgIds)
			->getQuery()
			->getResult();
	}

	public function getAllUsers(OauthUser $oauthUser)
	{
		$adminRole = $this->getAdminRole();
		$roleIds   = [$adminRole->getId()];
		$userId    = $oauthUser->getUid();
		// get the organisations that this user has direct access to as an admin or an owner
		$organisations = $this->entityManager->createQueryBuilder()
			->select('o.id')
			->from(Organization::class, 'o')
			->leftJoin(OrganizationAccess::class, 'oa', 'with', 'oa.organizationId = o.id')
			->where('(oa.roleId in (:roleIds) and oa.userId = :userId) or o.ownerId = :userId')
			->setParameter('roleIds', $roleIds)
			->setParameter('userId', $userId)
			->getQuery()
			->getArrayResult();

		$orgIds = [];
		foreach ($organisations as $organisation) {
			$orgIds[] = $organisation['id'];
			$childIds = $this->getOrgIdsDeep($organisation['id'], $userId, $roleIds);
			$orgIds   = array_merge($orgIds, $childIds);
		}

		return $this->entityManager->createQueryBuilder()
			->select('oa')
			->from(OrganizationAccess::class, 'oa')
			->where('oa.organizationId in (:orgIds)')
			->setParameter('orgIds', $orgIds)
			->getQuery()
			->getResult();
	}


	/**
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @param string $userId
	 * @param int $role
	 * @return OrganizationAccess
	 * @throws ORMException
	 * @throws OrganisationAccessDeniedException
	 * @throws OrganisationNotFoundException
	 */
	public function addUserById(OauthUser $oauthUser, UuidInterface $orgId, string $userId, int $role): OrganizationAccess
	{
		/** @var OauthUser $user */
		$user = $this->userRepository->find($userId);
		if (is_null($user)) {
			throw new OrganisationNotFoundException("Unknown user");
		}

		return $this->addUser($oauthUser, $orgId, $role, $user);
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @param string $email
	 * @param int $role
	 * @return OrganizationAccess
	 * @throws ORMException
	 * @throws OrganisationAccessDeniedException
	 * @throws OrganisationNotFoundException
	 */
	public function addUserByEmail(OauthUser $oauthUser, UuidInterface $orgId, string $email, int $role): OrganizationAccess
	{
		/** @var OauthUser $user */
		$user = $this->userRepository->findOneBy(["email" => $email]);
		if (is_null($user)) {
			throw new OrganisationNotFoundException("Unknown user");
		}

		return $this->addUser($oauthUser, $orgId, $role, $user);
	}

	/**
	 * @param OauthUser $currentUser
	 * @param UuidInterface $orgId
	 * @param int $role
	 * @param OauthUser $user
	 * @return OrganizationAccess
	 * @throws OrganisationAccessDeniedException
	 * @throws OrganisationNotFoundException
	 * @throws DBALException
	 */
	public function addUser(OauthUser $currentUser, UuidInterface $orgId, int $role, OauthUser $user): OrganizationAccess
	{
		$org = $this->getOrganisation($currentUser, $orgId, $this->getAdminRole());
		/** @var Role $role */
		$role = $this->roleRepository->findOneBy(["legacyId" => $role]);
		if (is_null($role)) {
			throw new OrganisationNotFoundException("Unknown role");
		}

		$orgId  = $orgId->toString();
		$userId = $user->getUid();
		$roleId = $role->getId()->toString();

		$conn        = $this->entityManager->getConnection();
		$deleteQuery = $conn->prepare("DELETE FROM `core`.`organization_access` WHERE organization_id = :organizationId AND user_id = :userId");
		$deleteQuery->bindParam('organizationId', $orgId);
		$deleteQuery->bindParam('userId', $userId);
		$deleteQuery->execute();

		$query = $conn
			->prepare("INSERT INTO `core`.`organization_access` (organization_id, user_id, role_id) VALUES (:organizationId, :userId, :roleId) ON DUPLICATE KEY UPDATE role_id = :roleId");

		$query->bindParam('organizationId', $orgId);
		$query->bindParam('userId', $userId);
		$query->bindParam('roleId', $roleId);
		$query->execute();
		/** @var OrganizationAccess $access */
		$access = $this
			->entityManager
			->getRepository(OrganizationAccess::class)
			->findOneBy(
				[
					'organizationId' => $orgId,
					'userId'         => $userId,
					'roleId'         => $roleId,
				]
			);
		return $access;
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param UuidInterface $orgId
	 * @param string $userId
	 * @return Organization
	 * @throws OrganisationAccessDeniedException
	 * @throws OrganisationNotFoundException
	 */
	public function removeUser(OauthUser $oauthUser, UuidInterface $orgId, string $userId): Organization
	{
		$org  = $this->getOrganisation($oauthUser, $orgId, $this->getAdminRole());
		$qb   = $this->entityManager->createQueryBuilder();
		$expr = $qb->expr();
		$qb->delete()
			->from(OrganizationAccess::class, 'oa')
			->where($expr->eq('oa.organizationId', ':organizationId'))
			->andWhere($expr->eq('oa.userId', ':userId'))
			->setParameters(
				[
					'organizationId' => $org->getId()->toString(),
					'userId'         => $userId,
				]
			)
			->getQuery()
			->execute();
		return $this->getOrganisationById($orgId->toString());
	}
}
