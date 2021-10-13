<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Reviews
 * @package App\Package\Profile\Data\Definitions
 */
class Reviews implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'Reviews';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	o.`name` AS `organization_name`,
    ls.`alias` AS `location_name`,
    ors.`subject` AS `review_page_subject`,
	ur.review AS `review_text`,
    ur.platform AS `platform`, 
    ur.sentiment AS `sentiment`,
    ur.score_positive,
    ur.score_negative,
    ur.score_mixed,
    ur.created_at AS `created_at`
FROM
    user_review ur
JOIN organization_review_settings ors ON ors.id = ur.review_settings_id
JOIN `organization` o ON o.id = ur.organization_id
LEFT JOIN location_settings ls ON ls.`serial` = ors.`serial`
WHERE ur.profile_id = :profileId ;',
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
                'DELETE ur FROM user_review ur WHERE ur.profile_id = :profileId',
                ['profileId' => $subject->getProfileId()]
            )
        ];
    }
}