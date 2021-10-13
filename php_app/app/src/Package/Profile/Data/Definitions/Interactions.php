<?php


namespace App\Package\Profile\Data\Definitions;


use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Filter;
use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\Filters\RemoveNull;
use App\Package\Profile\Data\ObjectDefinition;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Interactions
 * @package App\Package\Profile\Data\Definitions
 */
class Interactions implements Selectable, Deletable, Filterable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return "Interactions";
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        $query = "SELECT 
    i.created_at,
    ds.`name` AS `data_source`,
    o.`name` AS `organization_name`,
    ls.alias AS `location_name`,
    i.ended_at
FROM
    interaction_profile ip
JOIN interaction i ON i.id  = ip.interaction_id
JOIN data_source ds ON ds.id = i.data_source_id 
JOIN `organization` o ON o.id = i.organization_id
JOIN interaction_serial `is` ON `is`.interaction_id = i.id
JOIN location_settings ls ON ls.`serial` = `is`.`serial`
WHERE
    ip.profile_id = :profileId;";
        return new BaseStatement(
            $query,
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
	i
FROM interaction_profile ip
JOIN interaction i ON i.id = ip.interaction_id
WHERE
    ip.profile_id = :profileId;',
                [
                    'profileId' => $subject->getProfileId(),
                ]
            ),
        ];
    }


    /**
     * @return Filter[]
     */
    public function filters(): array
    {
        return [
            new RemoveNull(),
        ];
    }
}
