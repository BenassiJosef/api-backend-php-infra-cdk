<?php


namespace App\Package\Loyalty\StampScheme;

use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\Organization;
use App\Package\Loyalty\Reward\LoyaltyRewardFactory;
use App\Package\Loyalty\Reward\LoyaltyRewardProvider;
use Exception;

/**
 * Class StampSchemeFactory
 * @package App\Package\Loyalty\StampScheme
 */
class StampSchemeFactory
{
    /**
     * @return static
     */
    public static function defaultStampSchemeFactory(): self
    {
        return new self(
            LoyaltyRewardFactory::defaultLoyaltyRewardFactory()
        );
    }

    /**
     * @var LoyaltyRewardProvider $rewardProvider
     */
    private $rewardProvider;

    /**
     * StampSchemeFactory constructor.
     * @param LoyaltyRewardProvider $rewardProvider
     */
    public function __construct(LoyaltyRewardProvider $rewardProvider)
    {
        $this->rewardProvider = $rewardProvider;
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @return LoyaltyStampScheme
     * @throws Exception
     */
    public function make(Organization $organization, array $data): LoyaltyStampScheme
    {
        return LoyaltyStampScheme::fromArray(
            $organization,
            $this->rewardProvider->make($organization, $data['reward']),
            $data
        );
    }
}