<?php

namespace App\Package\Reviews\Reports;

use App\Package\Reports\OrganisationReportsRow;
use App\Package\Reports\Time;
use DateTime;
use Doctrine\ORM\EntityManager;

class ReportRepository
{

    const REPORT_QUERY = "SELECT 
date_format(r.created_at,'%Y-%m-%d%H') as row_key,
r.review_settings_id as review_settings_id,

CASE WHEN r.platform = 'nearly' THEN 'stampede' ELSE r.platform END AS platform,
   SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) AS one_star,
   SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) AS two_star,
SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) AS three_star,
SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) AS four_star,
SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) AS five_star,
SUM(CASE WHEN r.done_at IS NOT NULL THEN 1 ELSE 0 END) AS done,
SUM(CASE WHEN r.platform = 'google' THEN 1 ELSE 0 END) AS google,
SUM(CASE WHEN r.platform = 'stampede' THEN 1 ELSE 0 END) AS stampede,
SUM(CASE WHEN r.platform = 'facebook' THEN 1 ELSE 0 END) AS facebook,
SUM(CASE WHEN r.platform = 'tripadvisor' THEN 1 ELSE 0 END) AS tripadvisor,
SUM(CASE WHEN r.sentiment = 'MIXED' THEN 1 ELSE 0 END) AS mixed_sentiment,
SUM(CASE WHEN r.sentiment = 'NEUTRAL' THEN 1 ELSE 0 END) AS neutral_sentiment,
SUM(CASE WHEN r.sentiment = 'POSITIVE' THEN 1 ELSE 0 END) AS positive_sentiment,
SUM(CASE WHEN r.sentiment = 'NEGATIVE' THEN 1 ELSE 0 END) AS negative_sentiment,
   COUNT(DISTINCT(r.profile_id)) as users,
   COUNT(r.id) as reviews,
YEAR(r.created_at) as year, 
MONTH(r.created_at) as month, 
DAY(r.created_at) as day, 
DAYOFWEEK(r.created_at) as day_of_week,
HOUR(r.created_at) as hour 
FROM user_review r
LEFT JOIN organization_review_settings rs ON r.review_settings_id = rs.id
WHERE r.organization_id = :organisationId
AND rs.deleted_at IS NULL
AND r.rating IS NOT NULL
GROUP BY 
r.review_settings_id,
r.id,
YEAR(r.created_at),
MONTH(r.created_at),
DAY(r.created_at),
HOUR(r.created_at)
";


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

    public function getReviewSummary(
        string $organisationId,
        string $pageId = null
    ): ReportRow {
        /** @var ReportRow $report */
        $report = new ReportRow();
        $rows = $this->getReviewReportData($organisationId);
        foreach ($rows as $row) {
            if (!is_null($pageId) && $pageId !== $row['review_settings_id']) {
                continue;
            }
            $report->updateTotal(
                $row['row_key'],
                $row['review_settings_id'],
                $row['platform'],
                new Time(
                    $row['year'],
                    $row['month'],
                    $row['day'],
                    $row['day_of_week'],
                    $row['hour']
                ),
                (int) $row['users'] ?? 0,
                (int) $row['reviews'] ?? 0,
                (int) $row['one_star'] ?? 0,
                (int) $row['two_star'] ?? 0,
                (int) $row['three_star'] ?? 0,
                (int) $row['four_star'] ?? 0,
                (int) $row['five_star'] ?? 0,
                (int) $row['mixed_sentiment'] ?? 0,
                (int) $row['neutral_sentiment'] ?? 0,
                (int) $row['negative_sentiment'] ?? 0,
                (int) $row['positive_sentiment'] ?? 0,
                (int)$row['done'] ?? 0,
                (int)$row['google'] ?? 0,
                (int)$row['facebook'] ?? 0,
                (int)$row['tripadvisor'] ?? 0,
                (int)$row['stampede'] ?? 0,
            );
        }
        return $report;
    }

    public function getReviewReportData(string $organisationId): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::REPORT_QUERY);
        $query->bindParam('organisationId', $organisationId);
        $query->execute();
        return $query->fetchAll();
    }
}
