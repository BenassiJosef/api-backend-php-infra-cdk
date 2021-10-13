<?php


namespace App\Package\Loyalty\StampScheme;

use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\NotActiveException;
use App\Models\Loyalty\Exceptions\NotEnoughStampsException;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\OauthUser;

/**
 * Interface Redeemable
 * @package App\Package\Loyalty\StampScheme
 */
interface Redeemable
{
    /**
     * @param OauthUser|null $redeemer
     * @return LoyaltyReward
     * @throws NotActiveException
     * @throws NotEnoughStampsException
     * @throws AlreadyRedeemedException
     */
    public function redeem(OauthUser $redeemer = null): LoyaltyReward;
}