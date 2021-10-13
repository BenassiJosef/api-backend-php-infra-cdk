<?php


namespace App\Controllers\Locations\Reports\Overview;


use Doctrine\ORM\EntityManager;
use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class ReviewsView
 * @package App\Controllers\Locations\Reports\Overview
 */
final class ReviewsView implements View
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * ReviewsView constructor.
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
     * @throws \Exception
     */
    public function addDataToOverview(Overview $overview, array $serials): Overview
    {

        $qb    = $this
            ->entityManager
            ->createQueryBuilder();
        $expr  = $qb->expr();
        $query = $qb
            ->select(
                'lr.serial',
                'COUNT(lrc.id) AS reviews',
                'SUM(lrc.rating) AS total_stars'
            )
            ->from(ReviewSettings::class, 'lr')
            ->leftJoin(UserReview::class, 'lrc', Join::WITH, 'lr.id = lrc.reviewSettingsId')
            ->where($expr->isNotNull('lrc.id'))
            ->andWhere($expr->gte('lrc.createdAt', ':startDate'))
            ->andWhere($expr->lte('lrc.createdAt', ':endDate'))
            ->andWhere($expr->in('lr.serial', ':serials'))
            ->groupBy('lr.serial')
            ->setParameters(
                [
                    "serials"   => $serials,
                    "startDate" => $overview->getStartDate()->format("Y-m-d H:i:s"),
                    "endDate"   => $overview->getEndDate()->format("Y-m-d H:i:s")
                ]
            )
            ->getQuery();


        foreach ($query->execute() as $result) {
            $serial        = $result['serial'];
            $reviewsTotals = new ReviewTotals(
                (int)$result['reviews'],
                (int)$result['total_stars']
            );
            $totals        = $overview->getTotalsForSerial($serial) ?? new SiteTotals(new Totals(), $serial);
            $totals        = $totals->withAdditionalReviewsTotals($reviewsTotals);
            $overview      = $overview->withSiteTotals($totals);
        }
        return $overview;
    }
}
