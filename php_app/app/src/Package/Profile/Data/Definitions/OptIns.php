<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class OptIns
 * @package App\Package\Profile\Data\Definitions
 */
class OptIns implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'OptIns';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT
	o.`name`AS `organization_name`,
    `or`.created_at AS `registered_at`,
    `or`.last_interacted_at AS `last_interacted_at`,
    `or`.data_opt_in_at AS opted_in_to_data_at,
    `or`.sms_opt_in_at AS opted_in_to_sms_at,
    `or`.email_opt_in_at AS opted_in_to_email_at
FROM organization_registration `or`
JOIN `organization` o ON o.id = `or`.organization_id
WHERE `or`.profile_id = :profileId;',
            ['profileId' => $subject->getProfileId()]
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
                'DELETE reg FROM organization_registration reg WHERE reg.profile_id = :profileId;',
                [
                    'profileId' => $subject->getProfileId()
                ]
            )
        ];
    }
}