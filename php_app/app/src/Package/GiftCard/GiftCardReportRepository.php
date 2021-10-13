<?php


namespace App\Package\GiftCard;


use App\Models\Organization;
use Carbon\Traits\Date;
use Doctrine\ORM\EntityManager;
use DateTime;
use Ramsey\Uuid\Uuid;

class GiftCardReportRepository
{
    const Query = "
SELECT 
    gcs.id AS giftcard_settings_id, 
    iq.`total`,
    iq.`giftcards`,
    iq.`currency`,
    iq.`status`,
    gcs.title,
    o.name 
    FROM `core`.`gift_card_settings` gcs LEFT JOIN (SELECT 
        gc.gift_card_settings_id,
        SUM(gc.amount) AS `total`,
        COUNT(gc.id) AS giftcards,
        gc.currency,
        CASE
            WHEN gc.activated_at IS NOT NULL AND gc.redeemed_at IS NULL THEN 'active'
            WHEN gc.refunded_at IS NOT NULL THEN 'refunded'
            WHEN gc.redeemed_at IS NOT NULL THEN 'redeemed'
            ELSE 'unpaid'
        END AS `status`
FROM
    `core`.`gift_card` gc
WHERE coalesce(gc.redeemed_at, gc.activated_at, gc.created_at) BETWEEN :startDate AND :endDate
GROUP BY gc.gift_card_settings_id,
        gc.currency,
        (CASE
            WHEN gc.activated_at IS NOT NULL AND gc.redeemed_at IS NULL THEN 'active'
            WHEN gc.refunded_at IS NOT NULL THEN 'refunded'
            WHEN gc.redeemed_at IS NOT NULL THEN 'redeemed'
            ELSE 'unpaid'
        END)) iq ON iq.gift_card_settings_id = gcs.id LEFT JOIN `core`.`organization` o ON o.id = gcs.organization_id WHERE gcs.organization_id = :organizationId;    
    ";

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * GiftCardReportRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getReport(string $organizationId, DateTime $startDate, DateTime $endDate): array
    {
        /** @var GiftCardReportRow[] $report */
        $report = [];
        foreach ($this->getData($organizationId, $startDate, $endDate) as $row) {
            $settingsId = $row['giftcard_settings_id'];
            if (!array_key_exists($settingsId, $report)) {
                $report[$settingsId] = new GiftCardReportRow($row['title'], Uuid::fromString($settingsId), "GBP");
            }
            $report[$settingsId]->updateTotal(
                $row['status'] ?? 'active',
                $row['total'] ?? 0,
                $row['currency'] ?? 'GBP',
                $row['giftcards'] ?? 0
            );
        }
        return $report;
    }

    public function getData(string $organizationId, DateTime $startDate, DateTime $endDate): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::Query);
        $query->bindParam('organizationId', $organizationId);
        $formattedStart = $startDate->format('Y-m-d H:i:s');
        $formattedEnd   = $endDate->format('Y-m-d H:i:s');
        $query->bindParam('startDate', $formattedStart);
        $query->bindParam('endDate', $formattedEnd);
        $query->execute();
        return $query->fetchAll();
    }
}