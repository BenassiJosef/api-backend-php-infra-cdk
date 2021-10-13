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
 * Class GiftCard
 * @package App\Package\Profile\Data\Definitions
 */
class GiftCard implements Selectable, Deletable, Filterable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return "GiftCards";
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        $query = "SELECT 
    gc.id,
    gcs.title AS `scheme_title`,
    o.`name` AS `organization_name`,
    gc.transaction_id AS `stripe_transaction_id`,
    ls.alias AS `location_name`,
    gc.amount AS `amount`,
    gc.currency,
    gc.created_at,
    gc.activated_at,
    gc.redeemed_at,
    gc.refunded_at
FROM
    gift_card gc
JOIN gift_card_settings gcs ON gc.gift_card_settings_id = gcs.id
JOIN `organization` o ON o.id = gc.organization_id
LEFT JOIN location_settings ls ON ls.serial = gcs.serial
WHERE gc.profile_id = :profileId ;
";
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
                'DELETE gc FROM gift_card gc WHERE gc.profile_id = :profileId',
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
            new RemoveNull(),
            new Currency()
        ];
    }


}