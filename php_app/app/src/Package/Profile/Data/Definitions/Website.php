<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Website
 * @package App\Package\Profile\Data\Definitions
 */
class Website implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'Website';
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
                        w.url AS `website_url`,
                        wpc.cookie_id AS `cookie`,
                        wpc.created_at AS `profile_linked_at`,
                        wpc.lastvisit_at AS `profile_last_seen_at`,
                        wpc.visits AS `total_visits`,
                        we.event_type AS `event_type`,
                        we.page_path AS `page_path`,
                        we.referral_path AS `referral_path`,
                        we.created_at
                    FROM website_profile_cookies wpc
                    LEFT JOIN website_event we ON wpc.cookie_id = we.cookie
                    LEFT JOIN website w ON w.id = we.website_id
                    LEFT JOIN `organization` o ON o.id = w.organization_id
                    WHERE wpc.profile_id = :profileId
            ',
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
	we, wpc
FROM
    website_profile_cookies wpc
LEFT JOIN website_event we ON we.cookie = wpc.cookie_id
WHERE
    wpc.profile_id = :profileId;',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            )
        ];
    }
}