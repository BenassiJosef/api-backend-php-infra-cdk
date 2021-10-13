<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Stories
 * @package App\Package\Profile\Data\Definitions
 */
class Stories implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'NearlyStories';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	nsp.title AS `story_title`,
    nsp.subtitle AS `story_subtitle`,
    ls.`alias` AS `location_name`,
    o.`name` AS `organization_name`,
    nspa.impressionCreatedAt AS `created_at`,
    nspa.clickCreatedAt AS `clicked_at`,
    nspa.conversionCreatedAt AS `converted_at`
FROM
    nearly_story_page_activity nspa
JOIN nearly_story_page nsp 
	ON nsp.id = nspa.pageId
JOIN location_settings ls 
	ON ls.`serial` = nspa.`serial`
JOIN `organization` o ON o.id = ls.organization_id
WHERE profileId = :profileId;',
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
                'DELETE nspa FROM nearly_story_page_activity nspa WHERE nspa.profileId = :profileId',
                ['profileId' => $subject->getProfileId()]
            )
        ];
    }

}