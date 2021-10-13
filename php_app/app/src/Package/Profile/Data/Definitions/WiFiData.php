<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Filter;
use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\Filters\DataSize;
use App\Package\Profile\Data\Filters\RemoveNull;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class WiFiData
 * @package App\Package\Profile\Data\Definitions
 */
class WiFiData implements Selectable, Filterable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'WiFiSession';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
    ud.mac AS `mac_address`,
    ud.email,
    ls.`alias` AS `location_name`,
    o.`name` AS `organization_name`,
    ud.ip AS `session_ip`,
    IFNULL(ud.data_up, 0) as data_up,
    IFNULL(ud.data_down, 0) as data_down,
    ud.`timestamp` AS `connected_at`,
    ud.auth AS `did_auth`,
    ud.lastupdate AS `session_end`,
    ud.auth_time,
    ud.`type` AS `session_type`
FROM
    user_data ud
LEFT JOIN location_settings ls ON ud.`serial` = ls.`serial`
LEFT JOIN `organization` o ON o.id = ls.organization_id
WHERE ud.profileId = :profileId;',
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
                'DELETE ud FROM user_data ud WHERE ud.profileId = :profileId',
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
            new DataSize(
                'data_up',
                'data_down'
            ),
            new RemoveNull()
        ];
    }


}