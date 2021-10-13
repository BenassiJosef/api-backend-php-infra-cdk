<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class NearlyImpression
 * @package App\Package\Profile\Data\Definitions
 */
class NearlyImpression implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'NearlyImpressions';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
    ls.`alias` AS location_name,
    ni.impressionCreated AS `created_at`,
    ni.conversionCreated AS `converted_at`,
    ni.converted AS `did_convert`
FROM
    nearly_impressions ni
JOIN location_settings ls ON ls.`serial` = ni.`serial`
WHERE
    profileId = :profileId;',
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
                'DELETE ni FROM nearly_impressions ni WHERE ni.profileId = :profileId;',
                ['profileId' => $subject->getProfileId()]
            )
        ];
    }


}