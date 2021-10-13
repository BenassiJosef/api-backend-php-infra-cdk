<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class MacAddresses
 * @package App\Package\Profile\Data\Definitions
 */
class MacAddresses implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'MacAddress';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
    upma.mac_address,
    upma.created_at
FROM
    user_profile_mac_addresses upma
WHERE upma.profile_id = :profileId;',
            [
                'profileId' => $subject->getProfileId(),
            ],
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
                'DELETE upma 
FROM user_profile_mac_addresses upma 
WHERE
    upma.profile_id = :profileId',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            )
        ];
    }


}