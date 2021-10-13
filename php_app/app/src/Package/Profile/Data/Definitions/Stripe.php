<?php

namespace App\Package\Profile\Data\Definitions;

use App\Package\Database\BaseStatement;
use App\Package\Database\Statement;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Selectable;
use App\Package\Profile\Data\Subject;

/**
 * Class Stripe
 * @package App\Package\Profile\Data\Definitions
 */
class Stripe implements Selectable, Deletable
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'Stripe';
    }

    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement
    {
        return new BaseStatement(
            'SELECT 
	sc.stripe_user_id, 
    sc.created AS `created_at` 
FROM core.stripeCustomer sc 
WHERE profileId = :profileId;',
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
                'DELETE sc FROM stripeCustomer sc WHERE sc.profileId = :profileId',
                ['profileId' => $subject->getProfileId()]
            )
        ];
    }

}