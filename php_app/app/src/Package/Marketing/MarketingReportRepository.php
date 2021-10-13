<?php

namespace App\Package\Marketing;

use Doctrine\ORM\EntityManager;

class MarketingReportRepository
{

    const REPORT_QUERY = "SELECT 
mde.event,
MAX(mde.timestamp) as unix_time,
COUNT(mde.id) as count,
COUNT(DISTINCT(md.profileId)) as unique_count
FROM 
marketing_deliverability md 
LEFT JOIN marketing_deliverability_events mde ON md.id = mde.marketingDeliverableId
WHERE md.campaignId = :campaignId 
AND  mde.eventSpecificInfo NOT LIKE 'https://my.stampede.ai%'
GROUP BY mde.event";

    const ORGANISATION_REPORT_QUERY = "SELECT 
mde.event,
MAX(mde.timestamp) as unix_time,
COUNT(mde.id) as count,
COUNT(DISTINCT(md.profileId)) as unique_count
FROM 
marketing_campaigns mc
LEFT JOIN marketing_deliverability md ON mc.id = md.campaignId
LEFT JOIN marketing_deliverability_events mde ON md.id = mde.marketingDeliverableId
WHERE mc.organization_id = :organizationId
AND  mde.eventSpecificInfo NOT LIKE 'https://my.stampede.ai%'
GROUP BY mde.event";

    const ORGANISATION_CONVERSION_QUERY = "SELECT 
'in_venue_visit' as event,
mc.spendPerHead as spend_per_head,
COUNT(ud.profileId) as count,
COUNT(DISTINCT(ud.profileId)) as unique_count,
MAX(UNIX_TIMESTAMP(ud.timestamp)) as unix_time
FROM 
marketing_event me 
LEFT JOIN marketing_campaigns mc ON mc.id = me.campaignId 
LEFT JOIN user_data ud ON ud.profileId = me.profileId 
WHERE mc.organization_id = :organizationId
AND ud.timestamp > (NOW() - INTERVAL 6 MONTH)
AND ud.timestamp > (mc.created + INTERVAL 1 DAY)
AND ud.serial IN (SELECT serial FROM location_settings ls WHERE ls.organization_id = mc.organization_id)";

    const CONVERSION_QUERY = "SELECT 
'in_venue_visit' as event,
mc.spendPerHead as spend_per_head,
COUNT(ud.profileId) as count,
COUNT(DISTINCT(ud.profileId)) as unique_count,
MAX(UNIX_TIMESTAMP(ud.timestamp)) as unix_time
FROM 
marketing_event me 
LEFT JOIN marketing_campaigns mc ON mc.id = me.campaignId 
LEFT JOIN user_data ud ON ud.profileId = me.profileId 
WHERE me.campaignId = :campaignId
AND ud.timestamp > (NOW() - INTERVAL 6 MONTH)
AND ud.timestamp > (mc.created + INTERVAL 1 DAY)
AND ud.serial IN (SELECT serial FROM location_settings ls WHERE ls.organization_id = mc.organization_id)
";

    const EVENT_QUERY = "SELECT 
mde.event,
md.profileId,
mde.eventSpecificInfo AS info,
mde.timestamp AS unix_time,
up.id AS profileId,
up.email,
up.first, 
up.last
FROM 
marketing_deliverability md 
LEFT JOIN marketing_deliverability_events mde ON md.id = mde.marketingDeliverableId
LEFT JOIN user_profile up ON up.id = md.profileId
WHERE md.campaignId = :campaignId
AND mde.event = :event
AND  mde.eventSpecificInfo NOT LIKE 'https://my.stampede.ai%'
AND up.id IS NOT NULL
GROUP BY mde.id
ORDER BY mde.timestamp DESC";

    const CONVERSION_EVENT_QUERY = "SELECT 
'in_venue_visit' as event,
ud.serial as info,
up.id AS profileId,
up.email,
up.first, 
up.last,
UNIX_TIMESTAMP(ud.timestamp) as unix_time
FROM 
marketing_event me 
LEFT JOIN marketing_campaigns mc ON mc.id = me.campaignId 
LEFT JOIN user_data ud ON ud.profileId = me.profileId 
LEFT JOIN user_profile up ON up.id = ud.profileId 

WHERE me.campaignId = :campaignId
AND ud.timestamp > (NOW() - INTERVAL 6 MONTH)
AND ud.timestamp > (mc.created + INTERVAL 1 DAY)
AND up.id IS NOT NULL
AND ud.serial IN (SELECT serial FROM location_settings ls WHERE ls.organization_id = mc.organization_id)
GROUP BY ud.id
ORDER BY ud.timestamp DESC";

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

    public function getCampaign(string $campaignId, bool $includeCash = false): MarketingReportRow
    {
        /** @var MarketingReportRow $report */
        $report = new MarketingReportRow();
        foreach ($this->getCampaignData($campaignId) as $row) {
            $report->updateTotal(
                $row['event'],
                (int) $row['count'] ?? 0,
                (int) $row['unique_count'] ?? 0,
                $row['unix_time'] ?? 0
            );
        }
        if ($includeCash) {
            $row = $this->getCampaignConversionData($campaignId);
            $report->updateTotal(
                $row['event'],
                (int) $row['count'] ?? 0,
                (int) $row['unique_count'] ?? 0,
                $row['unix_time'] ?? 0
            );
            $report->updateInVenueSpend($row['spend_per_head'] ?? 0);
        }

        return $report;
    }

    public function getOrganisationCampaignReport(string $organisationId): MarketingReportRow
    {
        /** @var MarketingReportRow $report */
        $report = new MarketingReportRow();
        foreach ($this->getOrganisationCampaignData($organisationId) as $row) {
            $report->updateTotal(
                $row['event'],
                (int) $row['count'] ?? 0,
                (int) $row['unique_count'] ?? 0,
                $row['unix_time'] ?? 0
            );
        }
        $row = $this->getOrganisationCampaignConversionData($organisationId);
        $report->updateTotal(
            $row['event'],
            (int) $row['count'] ?? 0,
            (int) $row['unique_count'] ?? 0,
            $row['unix_time'] ?? 0
        );
        $report->updateInVenueSpend($row['spend_per_head'] ?? 0);

        return $report;
    }

    private function getOrganisationCampaignData(string $organisationId): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::ORGANISATION_REPORT_QUERY);
        $query->bindParam('organizationId', $organisationId);
        $query->execute();
        return $query->fetchAll();
    }

    private function getOrganisationCampaignConversionData(string $organisationId): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::ORGANISATION_CONVERSION_QUERY);
        $query->bindParam('organizationId', $organisationId);
        $query->execute();
        return $query->fetch();
    }

    private function getCampaignConversionData(string $campaignId): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::CONVERSION_QUERY);
        $query->bindParam('campaignId', $campaignId);
        $query->execute();
        return $query->fetch();
    }

    private function getCampaignData(string $campaignId): array
    {
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare(self::REPORT_QUERY);
        $query->bindParam('campaignId', $campaignId);
        $query->execute();
        return $query->fetchAll();
    }

    public function getCampaignEvent(string $campaignId, string $event): array
    {
        /** @var Event[] $report */
        $report = [];
        foreach ($this->getCampaignEventData($campaignId, $event) as $row) {

            $report[] = new Event(
                $row['profileId'],
                $row['info'],
                $row['unix_time'],
                $row['email'],
                $row['first'],
                $row['last']
            );
        }
        return $report;
    }


    private function getCampaignEventData(string $campaignId, string $event): array
    {
        $query = self::EVENT_QUERY;
        if ($event === 'in_venue_visit') {
            $query = self::CONVERSION_EVENT_QUERY;
        }
        $conn  = $this->entityManager->getConnection();
        $query = $conn->prepare($query);
        $query->bindParam('campaignId', $campaignId);
        if ($event !== 'in_venue_visit') {
            $query->bindParam('event', $event);
        }
        $query->execute();
        return $query->fetchAll();
    }
}
