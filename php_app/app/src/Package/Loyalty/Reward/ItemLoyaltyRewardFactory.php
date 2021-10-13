<?php


namespace App\Package\Loyalty\Reward;

use App\Models\Loyalty\LoyaltyReward;
use App\Models\Organization;
use App\Package\Organisations\OrganizationService;
use Exception;

class ItemLoyaltyRewardFactory implements LoyaltyRewardProvider
{
    /**
     * @param Organization $organization
     * @param array $data
     * @return LoyaltyReward
     * @throws Exception
     */
    public function make(Organization $organization, array $data): LoyaltyReward
    {
        $input = ItemLoyaltyRewardInput::fromArray($data);
        return LoyaltyReward::newItemReward(
            $organization,
            $input->getName(),
            $input->getCode()
        );
    }
}