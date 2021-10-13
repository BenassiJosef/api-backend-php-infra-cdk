<?php


namespace App\Package\Reports;

use App\Package\Reports\OrganisationReportsRow;
use App\Package\Reports\Time;
use DateTime;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;


class ReportRepository
{

	/**
	 * @var FromQuery $requestQuery
	 */

	private $requestQuery;

	private function whereClause()
	{
		$query = 'WHERE o.organization_id = :organisationId AND o.last_interacted_at BETWEEN :start AND :end';
		if (!$this->requestQuery->getSerial()) {
			return $query;
		}

		return "${query} AND (SELECT rs.serial FROM registration_source rs WHERE rs.organization_registration_id = o.id AND rs.serial = :serial LIMIT 1) IS NOT NULL";
	}

	private function query()
	{
		$where = $this->whereClause();
		$query = "SELECT date_format(o.last_interacted_at,'%Y-%m-%d%H') as row_key,
   		SUM(CASE WHEN o.created_at > :start THEN 1 ELSE 0 END) AS new_users,
		SUM(CASE WHEN o.last_interacted_at > DATE_SUB(:end, INTERVAL 45 MINUTE) THEN 1 ELSE 0 END) AS active_users,	
    	COUNT(o.profile_id) as users,
		SUM(CASE WHEN o.data_opt_in_at IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_user,
    	SUM(CASE WHEN o.email_opt_in_at IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_email_user,
    	SUM(CASE WHEN o.sms_opt_in_at IS NOT NULL THEN 1 ELSE 0 END) AS opt_in_sms_user,
		SUM(up.verified) AS email_verified, 
		YEAR(o.last_interacted_at) as year, 
		MONTH(o.last_interacted_at) as month, 
		DAY(o.last_interacted_at) as day, 
		DAYOFWEEK(o.last_interacted_at) as day_of_week,
		HOUR(o.last_interacted_at) as hour 
	FROM organization_registration o 
	LEFT JOIN user_profile up ON up.id = o.profile_id
	${where} 
	GROUP BY 
		YEAR(o.last_interacted_at),
		MONTH(o.last_interacted_at),
		DAY(o.last_interacted_at),
		HOUR(o.last_interacted_at)";

		return $query;
	}


	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * MarketingReportRepository constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function setQueryFromRequest(Request $request)
	{
		$query = new FromQuery($request);
		$this->requestQuery = $query;
		return $this;
	}

	public function getOrganisationRegistrations(): OrganisationReportsRow
	{

		/** @var OrganisationReportsRow $report */
		$report = new OrganisationReportsRow();
		$rows = $this->getOrganisationRegistrationData();
		foreach ($rows as $row) {

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
				(int) $row['active_users'] ?? 0,
				(int)$row['opt_in_user'] ?? 0,
				(int)$row['opt_in_email_user'] ?? 0,
				(int)$row['opt_in_sms_user'] ?? 0,
				(int) $row['email_verified'] ?? 0
			);
		}
		return $report;
	}

	public function getOrganisationRegistrationData(): array
	{
		$startTime = $this->requestQuery->getStartDate()->format("Y-m-d H:i:s");
		$endTime = $this->requestQuery->getEndDate()->format("Y-m-d H:i:s");
		$orgId =  $this->requestQuery->getOrganisationId();
		$serial = $this->requestQuery->getSerial();
		$conn  = $this->entityManager->getConnection();


		$query = $conn->prepare($this->query());
		$query->bindParam('organisationId', $orgId);
		$query->bindParam('start', $startTime);
		$query->bindParam('end', $endTime);
		if ($this->requestQuery->getSerial()) {
			$query->bindParam('serial', $serial);
		}
		$query->execute();
		return $query->fetchAll();
	}
}
