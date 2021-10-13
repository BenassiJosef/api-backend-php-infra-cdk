<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class MarketingCallback
 * @package App\Package\Profile\Data\Definitions
 */
class MarketingCallback implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return "MarketingCallbackEvents";
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	mc.`type` AS campaign_type,
    mc.eventTo AS destination,
    mc.`event` AS `event_type`,
    ls.alias AS `location_name`,
    o.`name` AS `organization_name`
FROM core.marketing_callback mc
JOIN location_settings ls ON ls.`serial` = mc.`serial`
JOIN `organization` o ON o.id = ls.organization_id
WHERE mc.profileId = :profileId
ORDER BY mc.id DESC;',
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
	mc
FROM core.marketing_callback mc 
WHERE mc.profileId = :profileId',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            )
        ];
    }


}