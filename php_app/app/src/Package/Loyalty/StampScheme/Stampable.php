<?php


namespace App\Package\Loyalty\StampScheme;


use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\OauthUser;
use App\Package\Loyalty\Stamps\StampContext;

interface Stampable
{
    /**
     * @param StampContext|null $context
     * @param int $stamps
     * @throws Exception
     * @throws FullCardException
     * @throws AlreadyRedeemedException
     * @throws OverstampedCardException
     */
    public function stamp(
        StampContext $context,
        int $stamps = 1
    ): void;
}
