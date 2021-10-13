<?php


namespace App\Package\Loyalty\Reward;

use App\Models\Loyalty\LoyaltyReward;
use App\Models\Organization;
use Exception;

class ValueLoyaltyRewardFactory implements LoyaltyRewardProvider
{
    /**
     * @param Organization $organization
     * @param array $data
     * @return LoyaltyReward
     * @throws Exception
     */
    public function make(Organization $organization, array $data): LoyaltyReward
    {
        $input = ValueLoyaltyRewardInput::fromArray($data);
        return LoyaltyReward::newValueReward(
            $organization,
            $input->getName(),
            $input->getAmount(),
            $input->getCurrency()
        );
    }
}
