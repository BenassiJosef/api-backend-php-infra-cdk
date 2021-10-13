<?php


namespace App\Package\Loyalty\Reward;

use App\Models\Loyalty\LoyaltyReward;
use App\Models\Organization;
use App\Package\Organisations\OrganizationService;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class LoyaltyRewardFactory
 * @package App\Package\Loyalty\Reward
 */
class LoyaltyRewardFactory implements LoyaltyRewardProvider
{
    /**
     * @return self
     */
    public static function defaultLoyaltyRewardFactory(): self
    {
        return (new self())
            ->registerRewardProvider(LoyaltyReward::TYPE_ITEM, new ItemLoyaltyRewardFactory())
            ->registerRewardProvider(LoyaltyReward::TYPE_VALUE, new ValueLoyaltyRewardFactory());
    }

    /**
     * @var LoyaltyRewardProvider[] $factoriesMap
     */
    private $factoriesMap = [];

    /**
     * @param Organization $organization
     * @param array $data
     * @return LoyaltyReward
     * @throws Exception
     */
    public function make(Organization $organization, array $data): LoyaltyReward
    {
        if (!array_key_exists('type', $data)) {
            throw new Exception('Specify a type');
        }
        $type = $data['type'];
        if (!array_key_exists($type, $this->factoriesMap)) {
            throw new Exception("${type} is not a valid type of LoyaltyReward");
        }
        return $this->factoriesMap[$type]->make($organization, $data);
    }

    /**
     * @param string $key
     * @param LoyaltyRewardProvider $loyaltyRewardProvider
     * @return self
     */
    public function registerRewardProvider(
        string $key,
        LoyaltyRewardProvider $loyaltyRewardProvider
    ): self {
        $this->factoriesMap[$key] = $loyaltyRewardProvider;
        return $this;
    }
}
