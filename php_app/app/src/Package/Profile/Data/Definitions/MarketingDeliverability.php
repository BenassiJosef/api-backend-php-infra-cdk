<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class MarketingDeliverability
 * @package App\Package\Profile\Data\Definitions
 */
class MarketingDeliverability implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'MarketingDeliverability';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT
	md.`type` AS `medium`,
    md.templateType AS `class`,
    ls.`alias` AS `location`,
    mc.`name` AS `campaign_name`,
    md.createdAt AS `sent_at`,
    mde.`event` AS `event_type`,
    mde.eventSpecificInfo AS `metadata`,
    FROM_UNIXTIME(mde.`timestamp`) AS `event_timestamp`,
    o.`name` AS `organization_name`
FROM
    marketing_deliverability md
JOIN marketing_deliverability_events mde 
	ON mde.marketingDeliverableId = md.id
LEFT JOIN marketing_campaigns mc ON md.campaignId = mc.id
LEFT JOIN location_settings ls ON ls.`serial` = md.`serial`
LEFT JOIN `organization` o ON o.id = COALESCE(mc.organization_id, ls.organization_id)
WHERE md.profileId = :profileId;',
            [
                'profileId' => $subject->getProfileId(),
            ]
        );
    }

    /**
     * @param Subject $subject
     * @return Statement[]
     */
    public function delete(Subject $subject): array
    {
        return [
            new BaseStatement(
                'DELETE
    mde, md
FROM
    marketing_deliverability md
JOIN marketing_deliverability_events mde ON md.id = mde.marketingDeliverableId
WHERE
    md.profileId = :profileId;',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            )
        ];
    }


}