<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Validation
 * @package App\Package\Profile\Data\Definitions
 */
class Validation implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'Validation';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	vs.email,
	vs.timestamp AS `validation_requested_at`
FROM validation_sent vs 
WHERE vs.profileId = :profileId;',
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
                'DELETE vs FROM validation_sent vs WHERE vs.profileId = :profileId',
                ['profileId' => $subject->getProfileId()]
            )
        ];
    }
}