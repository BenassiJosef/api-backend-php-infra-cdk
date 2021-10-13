<?php

namespace App\Package\Reports\Origin;

use App\Package\Reports\Origin\OriginReportRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class OriginReportController
{


	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * OriginReportController constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}


	public function getOriginReport(Request $request, Response $response)
	{

		$now       = new DateTime('now');
		$organisationId = $request->getAttribute('orgId');
		$serial   = $request->getParam('serial', null);
		$startDate   = (int) $request->getParam('startDate') ?? $now->sub(new DateInterval('P30D'))->getTimestamp();
		$endDate = (int) $request->getParam('endDate') ?? $now->getTimestamp();
		$report = new OriginReportRepository($this->entityManager);


		$data =  $report->getOriginInteractions(
			$organisationId,
			(new DateTime())->setTimestamp($startDate),
			(new DateTime())->setTimestamp($endDate),
			$serial
		);

		return $response->withJson($data, 200);
	}
}
