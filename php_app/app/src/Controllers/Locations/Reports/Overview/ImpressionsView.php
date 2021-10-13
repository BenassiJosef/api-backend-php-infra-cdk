<?php

namespace App\Controllers\Locations\Reports\Overview;


use App\Models\Nearly\ImpressionsAggregate;
use App\Models\UserRegistration;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class ImpressionsView
 * @package App\Controllers\Locations\Reports\Overview
 */
final class ImpressionsView implements View
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
            nia.serial,
            SUM(nia.impressions) AS impressions
            FROM " . ImpressionsAggregate::class . " nia
            WHERE nia.formattedTimestamp > :startDate
            AND nia.formattedTimestamp < :endDate
            AND nia.serial IN (:serials)
            GROUP BY nia.serial
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
            $totals   = $totals->withAdditionalSplashImpressions((int)$result['impressions']);
            $overview = $overview->withSiteTotals($totals);
        }
        return $overview;
    }
}