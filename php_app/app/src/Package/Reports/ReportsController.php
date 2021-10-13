<?php

namespace App\Package\Reports;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\PaginatedResponse;
use Doctrine\ORM\Query\Expr\OrderBy;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Slim\Http\Response;
use Slim\Http\Request;

class ReportsController
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
	 * ReportsController constructor.
	 * @param EntityManager $entityManager
	 * @param OrganizationProvider $organizationProvider
	 */
	public function __construct(EntityManager $entityManager, 		OrganizationProvider $organizationProvider)
	{
		$this->entityManager = $entityManager;
		$this->organizationProvider = $organizationProvider;
	}


	public function getOrganisationRegistrations(Request $request, Response $response)
	{

		$report = new ReportRepository($this->entityManager);
		$data = $report->setQueryFromRequest($request)
			->getOrganisationRegistrations();


		return $response->withJson($data, 200);
	}

	public function getOrganisationRegistrationsTotals(Request $request, Response $response)
	{

		$report = new ReportRepository($this->entityManager);
		$data = $report->setQueryFromRequest($request)
			->getOrganisationRegistrations();


		return $response->withJson($data, 200);
	}

	public function getOrganisationRegistrationsTable(Request $request, Response $response)
	{
		$params = new FromQuery($request);
		$organisation = $this->organizationProvider->organizationForRequest($request);

		$queryBuilder = $this->entityManager->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('o')
			->from(OrganizationRegistration::class, 'o')
			->leftJoin(RegistrationSource::class, 'r', Join::WITH, 'r.organizationRegistrationId = o.id')
			->where($expr->eq('o.organizationId', ':orgId'))
			->setParameter('orgId', $params->getOrganizationId())
			->andWhere($expr->between(
				'o.lastInteractedAt',
				':start',
				':end'
			))
			->setParameter('start', $params->getStartDate())
			->setParameter('end', $params->getEndDate());

		if (!is_null($params->getDataSourceId())) {
			$query = $query
				->andWhere(
					$expr->eq('r.dataSourceId', ':dataSource')
				)
				->setParameter('dataSource', $params->getDataSourceId());
		};

		if ($organisation->getIsRestrictedByLocation()) {
			$query = $query
				->andWhere(
					$expr->in('r.serial', ':serials')
				)
				->setParameter('serials', $organisation->getAccessableSerials());
		}

		if (!is_null($params->getSerial())) {
			$query = $query
				->andWhere(
					$expr->eq('r.serial', ':serial')
				)
				->setParameter('serial', $params->getSerial());
		} else if (!$organisation->getIsRestrictedByLocation()) {
			$query = $query
				->andWhere(
					$expr->isNull('r.serial')
				);
		}

		$query =      $query
			->orderBy(new OrderBy(
				'o.lastInteractedAt',
				$params->getSort()
			))
			->setMaxResults($params->getLimit())
			->setFirstResult($params->getOffset())->getQuery();



		return $response->withJson(new PaginatedResponse($query));
	}
}
