<?php


namespace App\Package\Loyalty\App;


use App\Models\Organization;
use App\Package\Loyalty\Reward\Reward;
use App\Package\Loyalty\StampCard\StampCard;
use App\Package\Loyalty\Stamps\StampContext;

interface AppLoyaltyScheme
{
    /**
     * @return LoyaltyOrganization
     */
    public function getOrganization(): LoyaltyOrganization;

    /**
     * @return LoyaltyLocation[]
     */
    public function getLocations(): array;

    /**
     * @return LoyaltyBranding
     */
    public function getBranding(): LoyaltyBranding;

    /**
     * @param StampContext $context
     * @return mixed
     */
    public function stamp(StampContext $context);

    /**
     * @return StampCard
     */
    public function currentCard(): StampCard;

    /**
     * @return Reward[]
     */
    public function redeemableRewards(): array;
}