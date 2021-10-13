<?php

namespace App\Package\Reports\Origin;

use App\Package\Reports\Origin\OriginInteraction;
use DateTime;
use Doctrine\ORM\EntityManager;

class OriginReportRepository
{

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

	public function query(?string $getSerial)
	{
		$query = "SELECT 
	up.id as profile_id, 
	up.gender as gender, 
    up.lat as lat, 
    up.lng as lng, 
    o.last_interacted_at as last_interacted_at, 
	rs.serial as visitedSerial 
FROM organization_registration o 
JOIN user_profile up ON up.id = o.profile_id 
JOIN registration_source rs ON rs.organization_registration_id = o.id WHERE o.organization_id = :organisationId 
AND o.last_interacted_at BETWEEN :start AND :end 
AND up.lat IS NOT NULL ";

		if (!is_null($getSerial)) {
			$query = $query . " AND rs.serial = :serial";
		}
		return $query . " GROUP BY o.id";
	}

	/**
	 * @return OriginInteraction[]
	 */
	public function getOriginInteractions(
		string $organisationId,
		DateTime $start,
		DateTime $end,
		?string $serial
	): array {
		/** @var OriginInteraction[] $report */
		$report = [];
		$rows = $this->getOriginInteractionsData($organisationId, $start, $end, $serial);
		foreach ($rows as $row) {
			array_push($report, new OriginInteraction(
				(int)$row['profile_id'],
				(float)$row['lat'],
				(float)$row['lng'],
				new DateTime($row['last_interacted_at']),
				$row['gender'],
				$row['visitedSerial']
			));
		}
		return $report;
	}

	public function getOriginInteractionsData(string $organisationId, DateTime $start, DateTime $end, ?string $serial): array
	{
		$startTime = $start->format("Y-m-d H:i:s");
		$endTime = $end->format("Y-m-d H:i:s");
		$conn  = $this->entityManager->getConnection();
		$query = $conn->prepare($this->query($serial));
		$query->bindParam('organisationId', $organisationId);
		$query->bindParam('start', $startTime);
		$query->bindParam('end', $endTime);

		if (!is_null($serial)) {
			$query->bindParam('serial', $serial);
		}

		$query->execute();
		return $query->fetchAll();
	}
}
