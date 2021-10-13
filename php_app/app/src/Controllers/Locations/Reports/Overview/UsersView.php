<?php


namespace App\Controllers\Locations\Reports\Overview;

use App\Models\UserRegistration;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class UsersView
 * @package App\Controllers\Locations\Reports\Overview
 */
final class UsersView implements View
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
            ur.serial,
            CASE WHEN ur.createdAt < :startDate THEN 1 ELSE 0 END AS is_new,
            COUNT(DISTINCT ur.profileId) AS users
            FROM " . UserRegistration::class . " ur
            WHERE ur.lastSeenAt > :startDate
            AND ur.lastSeenAt < :endDate
            AND ur.serial IN (:serials)
            GROUP BY ur.serial, is_new
        ";
        $query = $this->entityManager->createQuery($query)
                                     ->setParameters(
                                         [
                                             "serials"   => $serials,
                                             "startDate" => $overview->getStartDate()->format("Y-m-d H:i:s"),
                                             "endDate"   => $overview->getEndDate()->format("Y-m-d H:i:s")
                                         ]
                                     );
        foreach ($query->execute() as $result) {
            $serial = $result['serial'];
            $totals = $overview->getTotalsForSerial($result['serial']) ?? new SiteTotals(new Totals(), $serial);
            if ((bool)$result['is_new']) {
                $totals = $totals->withAdditionalReturningUsers((int)$result['users']);
            } else {
                $totals = $totals->withAdditionalNewUsers((int)$result['users']);
            }
            $overview = $overview->withSiteTotals($totals);
        }
        return $overview;
    }
}