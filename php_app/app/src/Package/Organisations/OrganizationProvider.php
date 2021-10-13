<?php


namespace App\Package\Organisations;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Auth\Access\User\UserRequestValidator;
use App\Package\Auth\UserContext;
use App\Package\Auth\UserSource;
use App\Package\Database\BaseStatement;
use App\Package\Database\FirstColumnFetcher;
use App\Package\Database\RawStatementExecutor;
use App\Package\Database\RowFetcher;
use App\Package\Exceptions\InvalidUUIDException;
use App\Package\Organisations\Exceptions\OrganizationNotFoundException;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Throwable;

class OrganizationProvider
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var FirstColumnFetcher $database
	 */
	private $database;

	/**
	 * OrganizationProvider constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->database      = new RawStatementExecutor($entityManager);
	}

	public function getOrganizationRegistration(Organization $organization, ?UserProfile $profile): ?OrganizationRegistration
	{
		if (is_null($profile)) {
			return null;
		}
		return $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy(
			[
				'profileId'      => $profile->getId(),
				'organizationId' => $organization->getId()
			]
		);
	}

	/**
	 * @param Request $request
	 * @return RequestLocations
	 * @throws InvalidUUIDException
	 * @throws OrganizationNotFoundException
	 */
	public function requestLocations(Request $request): RequestLocations
	{
		$requestedSerials = $this->requestedSerials($request);
		$serials       = $this->serialsForRequest($request);
		$root          = $this->organizationForRequest($request);
		if (count($requestedSerials) === 0 || count($serials) === 0) {
			return new RequestLocations(
				$root,
				$serials
			);
		}
		$organizations = $this->parentOrganizationsFromSerials($serials);
		$commonParent  = $this->commonParent($organizations);
		if ($commonParent->belongsTo($root)) {
			return new RequestLocations(
				$commonParent,
				$serials
			);
		}
		return new RequestLocations(
			$root,
			$serials
		);
	}

	/**
	 * @param string[] $serials
	 * @return Organization[]
	 */
	private function parentOrganizationsFromSerials(array $serials): array
	{
		$qb   = $this->entityManager->createQueryBuilder();
		$expr = $qb->expr();

		$organizations = $qb->select('o')
			->from(Organization::class, 'o')
			->innerJoin(
				LocationSettings::class,
				'ls',
				Join::WITH,
				'ls.organizationId = o.id'
			)
			->where(
				$expr->in('ls.serial', ':serials')
			)
			->setParameter('serials', $serials)
			->getQuery()
			->getResult();
		return $organizations;
	}

	/**
	 * @param Organization[] $organizations
	 * @return Organization
	 */
	private function commonParent(array $organizations): ?Organization
	{
		$deduplicated = $this->deduplicate($organizations);
		if (count($deduplicated) === 1) {
			return $deduplicated[0];
		}
		return $this->commonParent(
			$this->parents(
				$deduplicated
			)
		);
	}


	/**
	 * @param Organization[] $organizations
	 * @return Organization[]
	 */
	private function parents(array $organizations): array
	{
		$parents = [];
		foreach ($organizations as $organization) {
			$parents[] = $organization->getParent();
		}
		return $parents;
	}

	/**
	 * @param Organization[] $organizations
	 * @return Organization[]
	 */
	private function deduplicate(array $organizations): array
	{
		$map = [];
		foreach ($organizations as $organzation) {
			$map[$organzation->getId()->toString()] = $organzation;
		}
		return array_values($map);
	}

	/**
	 * @param Request $request
	 * @return Organization
	 * @throws InvalidUUIDException
	 * @throws OrganizationNotFoundException
	 */
	public function organizationForRequest(Request $request): Organization
	{
		$organizationId = $this->organizationIdFromRequest($request);
		/**
		 * @var Organization $organization
		 */
		$organization = $this
			->entityManager
			->getRepository(Organization::class)
			->find($organizationId);

		if ($organization === null) {
			throw new OrganizationNotFoundException($organizationId);
		}
		/**
		 * @var UserContext $source
		 */
		$userContext = $request->getAttribute(UserContext::class);
		/**
		 * @var UserSource $source
		 */
		$source = $request->getAttribute(UserSource::class);
		if (is_null($userContext)) {
			return $organization;
		}

		$userRoleChecker = new UserRoleChecker($this->entityManager);
		$userProvider = new UserProvider($this->entityManager);
		$user = $userProvider->getOauthUser($request);

		if (is_null($user)) {
			return $organization;
		}



		return $organization->getFilteredLocations(
			$userRoleChecker->locationSerials($userProvider->getOauthUser($request))
		);

		return $organization;
	}

	/**
	 * @param Request $request
	 * @return UuidInterface
	 * @throws InvalidUUIDException
	 */
	private function organizationIdFromRequest(Request $request): UuidInterface
	{
		$idString = $request->getAttribute('orgId');
		try {
			return Uuid::fromString($idString);
		} catch (Throwable $exception) {
			throw new InvalidUUIDException($idString, 'organizationId', $exception);
		}
	}

	public function serialsForRequest(Request $request): array
	{
		$requestedSerials = $this->requestedSerials($request);
		if (count($requestedSerials) === 0) {
			return $this->organizationSerials($request);
		}
		$organizationSerials = $this->organizationSerials($request);
		return array_intersect($requestedSerials, $organizationSerials);
	}

	private function requestedSerials(Request $request): array
	{
		$serials = $request->getQueryParam('serials', []);
		if (is_string($serials)) {
			return [$serials];
		}
		if (count($serials) === 0) {
			$serials = $request->getParsedBodyParam('serials', []);
		}
		return $serials;
	}

	private function organizationSerials(Request $request): array
	{
		$organization = $this->organizationForRequest($request);
		$query        = "WITH RECURSIVE cte (parent_organization_id, organization_id) AS (
	SELECT 
		o.parent_organization_id,
		o.id
	FROM `organization` o
    WHERE o.id = :organizationId
    UNION ALL
    SELECT
		child.parent_organization_id,
        child.id
	FROM `organization` child
    INNER JOIN cte ON child.parent_organization_id = cte.organization_id
) SELECT ls.`serial` 
FROM cte
INNER JOIN location_settings ls 
	ON ls.organization_id = cte.organization_id;";
		return $this->database->fetchFirstColumn(
			new BaseStatement(
				$query,
				[
					'organizationId' => $organization->getId()->toString(),
				]
			)
		);
	}
}
