<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class MarketingCampaigns
 * @package App\Package\Profile\Data\Definitions
 */
class MarketingCampaigns implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'MarketingCampaigns';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	me.`type` AS `medium`,
    me.`timestamp` AS `created_at`,
    me.eventTo AS `recipient`,
    ls.`alias` AS `location_name`,
    mc.`name` AS `campaign_name`,
    o.`name` AS `organization_name`
FROM marketing_event me 
JOIN location_settings ls ON ls.`serial` =  me.`serial`
JOIN marketing_campaigns mc ON mc.id = me.campaignId
LEFT JOIN marketing_campaign_events mce ON me.eventId = mce.id
LEFT JOIN `organization` o ON ls.organization_id = o.id
WHERE me.profileId = :profileId
ORDER BY me.`timestamp` DESC;',
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
	me
FROM marketing_event me
WHERE me.profileId = :profileId;',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            )
        ];
    }
}