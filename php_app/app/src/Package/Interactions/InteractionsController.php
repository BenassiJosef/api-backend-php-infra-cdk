<?php

namespace App\Package\Interactions;

use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\InteractionSerial;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\UserProfile;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Reports\FromQuery;
use App\Package\Reports\OrganisationReportsRow;
use App\Package\Reports\Time;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Slim\Http\Request;
use Slim\Http\Response;

class InteractionsController
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var OrganizationProvider $organizationProvider
	 */
	private $organizationProvider;


	/**
	 * InteractionsController constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		EntityManager $entityManager,
		OrganizationProvider $organizationProvider
	) {
		$this->entityManager = $entityManager;
		$this->organizationProvider = $organizationProvider;
	}

	public function fetchDataSources(Request $request, Response $response): Response
	{
		/**
		 * @var DataSource[] $sources
		 */
		$sources = $this->entityManager->getRepository(DataSource::class)->findAll();

		$res = [];
		foreach ($sources as $source) {
			$res[] = $source->jsonSerialize();
		}
		return $response->withJson($res);
	}


	public function fetchInteractions(Request $request, Response $response): Response
	{
		$organisation = $this->organizationProvider->organizationForRequest($request);
		$organizationId = $request->getAttribute('orgId');
		$limit = (int) $request->getQueryParam('limit', 25);
		$offset = (int) $request->getQueryParam('offset', 0);
		$dataSourceId = $request->getQueryParam('data_source', null);
		$email = $request->getQueryParam('email', null);
		$serial = $request->getQueryParam('serial', null);
		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('p')
			->from(InteractionProfile::class, 'p')
			->innerJoin(Interaction::class, 'c', 'WITH', 'p.interactionId = c.id')
			->where($expr->eq('c.organizationId', ':organizationId'))
			->setParameter('organizationId', $organisation->getId());



		if (!is_null($dataSourceId)) {
			$query = $query
				->andWhere($expr->eq('c.dataSourceId', ':dataSourceId'))
				->setParameter('dataSourceId', $dataSourceId);
		};

		if (!is_null($email)) {
			$query =  $query
				->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = p.profileId')
				->andWhere($expr->like('up.email', $expr->literal($email . '%')));
		}

		if (!is_null($serial)) {
			$query = $query
				->leftJoin(InteractionSerial::class, 's', 'WITH', 's.interactionId = c.id')
				->andWhere($expr->eq('s.serial', ':serial'))
				->setParameter('serial', $serial);

			if ($organisation->getIsRestrictedByLocation()) {
				$query = $query->andWhere($expr->in('s.serial', ':serials'))
					->setParameter('serials', $organisation->getAccessableSerials());
			}
		} else {
			if ($organisation->getIsRestrictedByLocation()) {
				$query = $query
					->leftJoin(InteractionSerial::class, 's', 'WITH', 's.interactionId = c.id')
					->andWhere($expr->in('s.serial', ':serials'))
					->setParameter('serials', $organisation->getAccessableSerials());
			}
		}



		$query = $query
			->orderBy(new OrderBy('c.createdAt', 'DESC'))
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getQuery();

		$resp = new PaginatedResponse($query);

		$this->entityManager->close();

		return $response->withJson($resp);
	}

	public function fetchRegistrationSource(Request $request, Response $response): Response
	{
		$params = new FromQuery($request);
		$timeKey = $params->getRegistrations()  ? 'r.createdAt' : 'r.lastInteractedAt';

		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('o')
			->from(OrganizationRegistration::class, 'o')
			->leftJoin(RegistrationSource::class, 'r', 'WITH', 'r.organizationRegistrationId = o.id')
			->where($expr->eq('o.organizationId', ':organizationId'))
			->andWhere($timeKey . ' BETWEEN :start AND :end')
			->setParameter('start', $params->getStartDate())
			->setParameter('end', $params->getEndDate())
			->setParameter('organizationId', $params->getOrganizationId());

		if (!is_null($params->getDataSourceId())) {
			$query = $query
				->andWhere($expr->eq('r.dataSourceId', ':dataSourceId'))
				->setParameter('dataSourceId', $params->getDataSourceId());
		};

		if (!is_null($params->getSerial())) {
			$query = $query
				->andWhere($expr->eq('r.serial', ':serial'))
				->setParameter('serial', $params->getSerial());
		} else {

			$query = $query
				->andWhere($expr->isNull('r.serial'));
		}
		$query = $query
			->orderBy(new OrderBy($timeKey, 'DESC'));

		$query =      $query->setMaxResults($params->getLimit())
			->setFirstResult($params->getOffset())->getQuery();

		$resp = new PaginatedResponse($query);

		$this->entityManager->close();

		return $response->withJson($resp);
	}

	public function fetchRegistrationTotalSource(Request $request, Response $response): Response
	{
		$params = new FromQuery($request);
		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();
		$timeKey = $params->getRegistrations()  ? 'r.createdAt' : 'r.lastInteractedAt';

		$organisation = $this->organizationProvider->organizationForRequest($request);

		$query = $queryBuilder
			->select(
				"
DATE_FORMAT(o.lastInteractedAt, :dateFormat) as row_key,
   SUM(CASE WHEN o.createdAt > :start THEN 1 ELSE 0 END) AS new_users,
SUM(up.verified) as email_verified, 
   COUNT(DISTINCT(o.profileId)) as users,
    SUM(CASE WHEN o.dataOptInAt IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_user,
    SUM(CASE WHEN o.emailOptInAt IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_email_user,
    SUM(CASE WHEN o.smsOptInAt IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_sms_user,
YEAR(o.lastInteractedAt) as year, 
MONTH(o.lastInteractedAt) as month, 
DAY(o.lastInteractedAt) as day, 
DAYOFWEEK(o.lastInteractedAt) as day_of_week,
HOUR(o.lastInteractedAt) as hour"
			)
			->from(OrganizationRegistration::class, 'o')
			->leftJoin(RegistrationSource::class, 'r', Join::WITH, 'r.organizationRegistrationId = o.id')
			->leftJoin(UserProfile::class, 'up', Join::WITH, 'up.id = o.profileId')
			->where($expr->eq('o.organizationId', ':organizationId'))
			->andWhere($timeKey . ' BETWEEN :start AND :end')
			->setParameter('start', $params->getStartDate())
			->setParameter('end', $params->getEndDate())
			->setParameter('dateFormat', ' %Y-%m-%d%H')
			->setParameter('organizationId', $params->getOrganizationId());

		if (!is_null($params->getDataSourceId())) {
			$query = $query
				->andWhere($expr->eq('r.dataSourceId', ':dataSourceId'))
				->setParameter('dataSourceId', $params->getDataSourceId());
		};

		if (!is_null($params->getSerial())) {
			$query = $query
				->andWhere($expr->eq('r.serial', ':serial'))
				->setParameter('serial', $params->getSerial());
		} else {
			if ($organisation->getIsRestrictedByLocation()) {
				$query = $query
					->andWhere($expr->in('r.serial', ':serials'))
					->setParameter('serials', $organisation->getAccessableSerials());
			} else {
				$query = $query
					->andWhere($expr->isNull('r.serial'));
			}
		}


		$query = $query->groupBy('year, month, day, hour');

		$results = $query->getQuery()->getArrayResult();

		$report = new OrganisationReportsRow();
		foreach ($results as $row) {
			$report->updateTotal(
				$row['row_key'],
				new Time(
					$row['year'],
					$row['month'],
					$row['day'],
					$row['day_of_week'],
					$row['hour']
				),
				(int) $row['new_users'] ?? 0,
				(int) $row['users'] ?? 0,
				0,
				(int) $row['opt_in_user'] ?? 0,
				(int) $row['opt_in_email_user'] ?? 0,
				(int) $row['opt_in_sms_user'] ?? 0,
				(int)$row['email_verified'] ?? 0
			);
		}

		return $response->withJson($report);
	}
}
