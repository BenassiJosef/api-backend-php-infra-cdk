<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Filterable;
use App\Package\Profile\Data\Filters\Currency;
use App\Package\Profile\Data\Filters\RemoveNull;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;
use PHP_CodeSniffer\Filters\Filter;

/**
 * Class WiFiPayments
 * @package App\Package\Profile\Data\Definitions
 */
class WiFiPayments implements Selectable, Filterable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'WiFiPayments';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            "SELECT 
	up.email,
    ls.`alias` AS location_name,
    o.`name` AS organization_name,
    lp.`name` AS `plan_name`,
    lp.deviceAllowance AS `plan_device_allowance`,
    lp.duration AS `plan_duration`,
    lp.cost AS `plan_cost`,
    up.creationdate AS `created_at`,
    up.`status` AS `status`,
    up.`duration` AS `duration`,
    up.payment_amount AS `amount`,
    'GBP' AS `currency`,
    up.transaction_id,
    up.payment_amount,
    up.devices,
    up.reason
FROM user_payments up
LEFT JOIN location_settings ls ON ls.`serial` = up.`serial`
LEFT JOIN `organization` o ON o.id = ls.organization_id
LEFT JOIN location_plans lp ON lp.id = up.planId
WHERE up.profileId = :profileId",
            [
                'profileId' => $subject->getProfileId()
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
                'DELETE up FROM user_payments up WHERE up.profileId = :profileId;',
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