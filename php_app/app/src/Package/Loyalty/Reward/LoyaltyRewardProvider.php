<?php


namespace App\Package\Loyalty\Reward;

use App\Models\Loyalty\LoyaltyReward;
use App\Models\Organization;

/**
 * Interface LoyaltyRewardProvider
 * @package App\Package\Loyalty
 */
interface LoyaltyRewardProvider
{

    /**
     * @param Organization $organization
     * @param array $data
     * @return LoyaltyReward
     */
    public function make(Organization $organization, array $data): LoyaltyReward;
}
