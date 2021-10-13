<?php


namespace App\Controllers\Locations\Reports\Overview;


use App\Models\Nearly\ImpressionsAggregate;
use App\Models\UserData;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class ConnectionsView
 * @package App\Controllers\Locations\Reports\Overview
 */
final class ConnectionsView implements View
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * UsersView constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Overview $overview
     * @param array $serials
     * @return Overview
     * @throws Exception
     */
    public function addDataToOverview(Overview $overview, array $serials): Overview
    {
        $query = "SELECT 
            ud.serial,
            SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) AS dwell_time,
            COUNT(ud.id) AS connections
            FROM " . UserData::class . " ud
            WHERE ud.timestamp > :startDate
            AND ud.lastupdate < :endDate
            AND ud.lastupdate IS NOT NULL
            AND ud.serial IN (:serials)
            GROUP BY ud.serial
        ";
        $query = $this
            ->entityManager
            ->createQuery($query)
            ->setParameters(
                [
                    "serials"   => $serials,
                    "startDate" => $overview->getStartDate()->format("Y-m-d H:i:s"),
                    "endDate"   => $overview->getEndDate()->format("Y-m-d H:i:s")
                ]
            );
        foreach ($query->execute() as $result) {
            $serial   = $result['serial'];
            $totals   = $overview->getTotalsForSerial($serial) ?? new SiteTotals(new Totals(), $serial);
            $totals   = $totals
                ->withAdditionalConnections((int)$result['connections'])
                ->withAdditionalDwellTime((int)$result['dwell_time']);
            $overview = $overview->withSiteTotals($totals);
        }
        return $overview;
    }
}