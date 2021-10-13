<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Filter;
use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\Filters\Allow;
use App\Package\Profile\Data\Filters\Merge;
use App\Package\Profile\Data\Filters\RemoveNull;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Profile
 * @package App\Package\Profile\Data\Definitions
 */
class Profile implements Selectable, Filterable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return "Profile";
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT up.* FROM `core`.`user_profile` up WHERE up.id = :userId',
            [
                'userId' => $subject->getProfileId()
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
                'DELETE up FROM `core`.`user_profile` up WHERE up.id = :profileId;',
                [
                    'profileId' => $subject->getProfileId(),
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
            new Allow(
                'email',
                'first',
                'last',
                'phone',
                'postcode',
                'age',
                'birth_month',
                'birth_day',
                'gender',
                'timestamp',
                'updated',
                'country'
            ),
            new RemoveNull(),
            new Merge(
                '%d-%d',
                'birth-date',
                'birth_day',
                'birth_month'
            )
        ];
    }
}