<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Filter;
use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\Filters\Currency;
use App\Package\Profile\Data\Filters\RemoveNull;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Loyalty
 * @package App\Package\Profile\Data\Definitions
 */
class Loyalty implements Selectable, Deletable, Filterable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'Loyalty';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT
	lr.`name` AS `reward_name`,
    lr.amount,
    lr.currency,
    lr.`type` AS `reward_type`,
    o.`name` AS `organization_name`,
    ls.`alias` AS `location_name`,
    lsc.collected_stamps,
	lss.required_stamps,
    lsc.created_at,
    lsc.activated_at,
    lsc.last_stamped_at,
    lsc.redeemed_at
FROM loyalty_stamp_card lsc
JOIN loyalty_stamp_scheme lss ON lss.id = lsc.scheme_id
LEFT JOIN location_settings ls ON ls.`serial` = lss.`serial`
JOIN loyalty_reward lr ON lss.reward_id = lr.id
JOIN `organization` o ON o.id = lss.organization_id
WHERE lsc.profile_id = :profileId;',
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
                'DELETE lsc
FROM
    loyalty_stamp_card lsc
WHERE lsc.profile_id = :profileId;',
                [
                    'profileId' => $subject->getProfileId()
                ]
            )
        ];
    }

    /**
     * @return Filter[]
     */
    public function filters(): array
    {
        return [
            new RemoveNull(),
            new Currency()
        ];
    }

}