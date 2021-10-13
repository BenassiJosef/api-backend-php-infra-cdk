<?php


namespace App\Package\Organisations;


use App\Models\LocationAccess;
use App\Models\Locations\LocationSettings;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Package\Database\BaseStatement;
use App\Package\Database\PaginationStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Database\RowFetcher;
use App\Package\Organisations\RBAC\OrganizationIdsByUserAndRolesStatement;
use App\Package\Organisations\RBAC\SerialsByUserAndRolesStatement;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use App\Models\Role;
use Doctrine\DBAL\Statement;
use Slim\Http\Request;
use Slim\Http\Response;
use YaLinqo\Enumerable;

/**
 * Class UserRoleChecker
 * @package App\Package\Organisations
 */
class UserRoleChecker
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @var RowFetcher
	 */
	private $rowFetcher;

	/**
	 * UserRoleChecker constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->rowFetcher    = new RawStatementExecutor($entityManager);
	}

	/**
	 * @param OauthUser $oauthUsers
	 * @param Role[]|null $roles
	 * @return Organization[]
	 */
	public function organisations(OauthUser $oauthUsers, array $roles = []): array
	{
		$orgIds        = $this
			->organizationIds($oauthUsers, $roles);
		$organizations = $this
			->entityManager
			->getRepository(Organization::class)
			->findBy(
				[
					'id' => $orgIds,
				]
			);

		return from($organizations)
			->select(
				function (Organization $organization): Organization {
					return $organization;
				},
				function (Organization $organization): string {
					return $organization->getId()->toString();
				}
			)
			->toArray();
	}

	public function organisationAccess(OauthUser $oauthUsers): array
	{
		return $this
			->entityManager
			->getRepository(OrganizationAccess::class)
			->findBy(
				[
					"userId" => $oauthUsers->getUid(),
				]
			);
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param Role[]|null $roles
	 * @return UuidInterface[]
	 */
	public function organizationIds(OauthUser $oauthUser, array $roles = []): array
	{
		$rows = $this
			->rowFetcher
			->fetchAll(
				new OrganizationIdsByUserAndRolesStatement(
					$oauthUser,
					$roles
				)
			);
		return from($rows)
			->select(
				function (array $row): string {
					return $row['id'];
				}
			)
			->select(
				function (string $orgId): UuidInterface {
					return Uuid::fromString($orgId);
				}
			)
			->toArray();
	}


	/**
	 * @param string $userId
	 * @param Role[] | null $roles
	 * @return LocationSettings[]
	 * @throws Exception
	 */
	public function locationForUserId(string $userId, array $roles = []): array
	{
		/** @var OauthUser $user */
		$user = $this
			->entityManager
			->getRepository(OauthUser::class)
			->findOneBy(['uid' => $userId]);
		if ($user === null) {
			throw new Exception("Cannot find user for id ($userId)");
		}
		return $this->locations($user, $roles);
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param Role[] | null $roles
	 * @return LocationSettings[]
	 */
	public function locations(OauthUser $oauthUser, array $roles = []): array
	{
		$serials = $this
			->locationSerialsForRoles($oauthUser, $roles);

		/** @var LocationSettings[] $locations */
		$locations = $this
			->entityManager
			->getRepository(LocationSettings::class)
			->findBy(
				[
					'serial' => $serials,
				]
			);
		return from($locations)
			->select(
				function (LocationSettings $locationSettings): LocationSettings {
					return $locationSettings;
				},
				function (LocationSettings $locationSettings): string {
					return $locationSettings->getSerial();
				}
			)
			->toArray();
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param string[] $types
	 * @param int[] $legacyRoleIds
	 * @return bool
	 * @throws DBALException
	 */
	public function hasAccessToOrganizationType(OauthUser $oauthUser, array $types = [], array $legacyRoleIds = []): bool
	{
		if (count($legacyRoleIds) === 0) {
			$legacyRoleIds = Role::$allRoles;
		}
		$keyedTypes = $this->inKey($types, 'orgType');
		$keyedRoles = $this->inKey($legacyRoleIds, 'role');
		$params     = array_merge(
			[
				'userId' => $oauthUser->getUid(),
			],
			$keyedTypes,
			$keyedRoles
		);

		$typeString  = $this->inString($keyedTypes);
		$rolesString = $this->inString($keyedRoles);
		$query       = "WITH RECURSIVE cte (id, parent_organization_id, type) as (SELECT 
    o.id,
    o.parent_organization_id,
    o.type
FROM
    organization o
        LEFT JOIN
    organization_access oa ON oa.organization_id = o.id
		LEFT JOIN `role` r ON oa.role_id = r.id
WHERE
    o.owner_id = :userId
        OR (oa.user_id = :userId AND r.legacy_id IN($rolesString))
    UNION ALL 
    SELECT 
		child.id,
        child.parent_organization_id,
        child.type
	FROM organization child INNER JOIN cte ON child.parent_organization_id = cte.id)
SELECT * FROM cte WHERE cte.type IN ($typeString);";

		return $this->executeHasRows($query, $params);
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param int[] $legacyRoleIds
	 * @return LocationSettings[];
	 */
	public function locationsForLegacyRoles(
		OauthUser $oauthUser,
		array $legacyRoleIds = []
	): array {
		$roles = $this
			->entityManager
			->getRepository(Role::class)
			->findBy(
				[
					"legacyId" => $legacyRoleIds
				]
			);
		return $this->locations($oauthUser, $roles);
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param Role[] $roles
	 * @return string[]
	 * @throws DBALException
	 */
	public function locationSerialsForRoles(OauthUser $oauthUser, array $roles = []): array
	{
		$rows = $this
			->rowFetcher
			->fetchAll(
				new SerialsByUserAndRolesStatement($oauthUser, $roles)
			);

		$locationAccessStatement = new BaseStatement(
			"SELECT la.serial, ls.organization_id FROM location_access la 
LEFT JOIN location_settings ls ON ls.serial = la.serial
WHERE user_id = :user_id",
			['user_id' => $oauthUser->getUserId()]
		);

		$locationRows = $this
			->rowFetcher
			->fetchAll(
				$locationAccessStatement
			);

		$locationSerials = from($locationRows)
			->select(
				function (array $row): string {
					return $row['serial'];
				}
			)
			->toArray();

		$filterOrgId = from($locationRows)
			->select(
				function (array $row): string {
					return $row['organization_id'];
				}
			)
			->toArray();


		$organisationSerials = from($rows)
			->where(function (array $row) use ($filterOrgId): bool {
				return !in_array($row['organization_id'], $filterOrgId);
			})
			->select(
				function (array $row): string {
					return $row['serial'];
				}
			)
			->toArray();


		return array_values(array_merge($locationSerials, $organisationSerials));
	}

	/**
	 * @param OauthUser $oauthUser
	 * @return string[]
	 * @throws DBALException
	 */
	public function locationSerials(OauthUser $oauthUser): array
	{
		$serials = $this->locationSerialsForRoles($oauthUser);
		$oauthUser->setAccess($serials);
		return $serials;
	}

	/**
	 * @param mixed[] $legacyRoleIds
	 * @param string $prefix
	 * @return int[]
	 */
	private function inKey(array $legacyRoleIds, string $prefix): array
	{
		$keyedIds = [];
		foreach ($legacyRoleIds as $i => $legacyRoleId) {
			$keyedIds["${prefix}_${i}"] = $legacyRoleId;
		}
		return $keyedIds;
	}

	/**
	 * @param array $params
	 * @return string
	 */
	private function inString(array $params): string
	{
		return from($params)
			->toKeys()
			->select(
				function (string $key): string {
					return ":${key}";
				}
			)
			->toString(',');
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return bool
	 * @throws DBALException
	 */
	private function executeHasRows(string $query, array $params): bool
	{
		$prepared = $this
			->entityManager
			->getConnection()
			->prepare($query);
		$keys     = array_keys($params);
		foreach ($keys as $key) {
			$prepared->bindParam($key, $params[$key]);
		}
		if (!$prepared->execute()) {
			throw new Exception('could not query DB');
		}
		return $prepared->rowCount() > 0;
	}

	/**
	 * @param OauthUser $user
	 * @param string $orgId
	 * @param int[] $legacyRoleIds
	 * @return bool
	 * @throws DBALException
	 */
	public function hasAccessToOrganizationAsRole(OauthUser $user, string $orgId, array $legacyRoleIds = []): bool
	{
		if (count($legacyRoleIds) === 0) {
			$legacyRoleIds = Role::$allRoles;
		}
		$keyedRoles = $this->inKey($legacyRoleIds, 'role');
		$params     = array_merge(
			[
				'organizationId' => $orgId,
				'userId'         => $user->getUid(),
			],
			$keyedRoles
		);
		$roleString = $this->inString($keyedRoles);

		$query = "
       WITH RECURSIVE cte (id, parent_organization_id) as (SELECT 
    o.id,
    o.parent_organization_id
FROM
    organization o
        LEFT JOIN
    organization_access oa ON oa.organization_id = o.id
		LEFT JOIN `role` r ON oa.role_id = r.id
WHERE
    o.owner_id = :userId
        OR (oa.user_id = :userId AND r.legacy_id IN($roleString))
    UNION ALL 
    SELECT 
		child.id,
        child.parent_organization_id
	FROM organization child INNER JOIN cte ON child.parent_organization_id = cte.id)
SELECT * FROM cte WHERE cte.id = :organizationId;";
		return $this->executeHasRows($query, $params);
	}

	/**
	 * @param OauthUser $user
	 * @param string $serial
	 * @param int[] $legacyRoleIds
	 * @return bool
	 * @throws DBALException
	 */
	public function hasAccessToLocationAsRole(OauthUser $user, string $serial, array $legacyRoleIds = []): bool
	{
		if (count($legacyRoleIds) === 0) {
			$legacyRoleIds = Role::$allRoles;
		}
		$keyedRoles = $this->inKey($legacyRoleIds, 'role');
		$params     = array_merge(
			[
				'locationSerial' => $serial,
				'userId'         => $user->getUid(),
			],
			$keyedRoles
		);
		$roleString = $this->inString($keyedRoles);
		$query      = "WITH RECURSIVE cte (id, parent_organization_id) as (SELECT 
    o.id,
    o.parent_organization_id
FROM
    organization o
        LEFT JOIN
    organization_access oa ON oa.organization_id = o.id
		LEFT JOIN `role` r ON oa.role_id = r.id
WHERE
    o.owner_id = :userId
        OR (oa.user_id = :userId AND r.legacy_id IN($roleString))
    UNION ALL 
    SELECT 
		child.id,
        child.parent_organization_id
	FROM organization child INNER JOIN cte ON child.parent_organization_id = cte.id)
SELECT * FROM cte
LEFT JOIN location_settings ls ON ls.organization_id = cte.id
WHERE ls.serial = :locationSerial;";
		return $this->executeHasRows($query, $params);
	}

	/**
	 * @param int[] $legacyRoleIds
	 * @return Role[]
	 */
	private function rolesForLegacyIds(array $legacyRoleIds): array
	{
		/** @var Role[] $roles */
		$roles = $this
			->entityManager
			->getRepository(Role::class)
			->findBy(['legacyId' => $legacyRoleIds]);
		return $roles;
	}

	/**
	 * @param OauthUser $oauthUsers
	 * @param Role[]|null $roles
	 * @return array
	 */
	private function organizationLocationAccess(OauthUser $oauthUsers, array $roles = []): array
	{
		$organizations = $this->organisations($oauthUsers, $roles);
		$locations     = [];
		foreach ($organizations as $organization) {
			foreach ($organization->getLocations() as $location) {
				$locations[$location->getSerial()] = $location;
			}
		}
		return $locations;
	}

	/**
	 * @param OauthUser $oauthUser
	 * @param Role[] | null $roles
	 * @return array
	 */
	private function locationAccess(OauthUser $oauthUser, array $roles = []): array
	{
		$qb = $this
			->entityManager
			->createQueryBuilder();

		$expr         = $qb->expr();
		$parameters   = [
			'userId' => $oauthUser->getUid()
		];
		$partialQuery = $qb
			->select('ls')
			->from(LocationAccess::class, 'la')
			->leftJoin(
				LocationSettings::class,
				'ls',
				Join::WITH,
				'ls.serial = la.serial'
			)->where($expr->eq('la.userId', ':userId'));

		if (count($roles) > 0) {
			$partialQuery
				->andWhere($expr->in('la.roleId', ':roles'));
			$parameters['roles'] = $this->roleIds($roles);
		}
		$query = $partialQuery->setParameters($parameters)->getQuery();

		$locations = [];
		/** @var $location LocationSettings */
		foreach ($query->getResult() as $location) {
			$locations[$location->getSerial()] = $location;
		}
		return $locations;
	}

	/**
	 * @param Role[] $roles
	 * @return string[]
	 */
	private function roleIds(array $roles = []): array
	{
		$ids = [];
		foreach ($roles as $role) {
			$ids[] = $role->getId();
		}
		return $ids;
	}

	/**
	 * @param OauthUser $user
	 * @param string $subjectUid
	 * @return bool true if the user has access to a user identified by subjectUid
	 */
	public function hasAdminAccessToUser(OauthUser $user, string $subjectUid): bool
	{
		$adminRole       = $this->entityManager->getRepository(Role::class)->findOneBy(["legacyId" => Role::LegacyAdmin]);
		$organisationIds = $this->organizationIds($user, [$adminRole]);

		// check by organisation acess
		$qb   = $this->entityManager->createQueryBuilder();
		$expr = $qb->expr();

		$orgQuery = $qb->select('oa')
			->from(OrganizationAccess::class, 'oa')
			->where(
				$expr->andX(
					$expr->in('oa.organizationId', ':organisationIds'),
					$expr->eq('oa.userId', ':subjectUid')
				)
			);

		$orgParameters = [
			'organisationIds' => $organisationIds,
			'subjectUid'      => $subjectUid
		];

		$orgQuery = $orgQuery->setParameters($orgParameters)->getQuery();
		$result   = $orgQuery->getResult();
		if (count($result) > 0) {
			return true;
		}

		// check by location access
		$locationSerials = $this->organizationLocationAccess($user, [$adminRole]);

		$locationQuery = $qb->select('la')
			->from(LocationAccess::class, 'la')
			->where(
				$expr->andX(
					$expr->in('la.serial', ':locationSerials'),
					$expr->eq('la.userId', ':subjectUid')
				)
			);

		$locationParameters = [
			'locationSerials' => array_keys($locationSerials),
			'subjectUid'      => $subjectUid
		];

		$locationQuery = $locationQuery->setParameters($locationParameters)->getQuery();
		$result        = $locationQuery->getResult();
		if (count($result) > 0) {
			return true;
		}
		return false;
	}
}
