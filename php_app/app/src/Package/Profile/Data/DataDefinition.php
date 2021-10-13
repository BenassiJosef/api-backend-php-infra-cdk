<?php


namespace App\Package\Profile\Data;

use App\Package\Database\Database;
use App\Package\Profile\Data\Definitions\GiftCard;
use App\Package\Profile\Data\Definitions\Interactions;
use App\Package\Profile\Data\Definitions\Loyalty;
use App\Package\Profile\Data\Definitions\MacAddresses;
use App\Package\Profile\Data\Definitions\MarketingCallback;
use App\Package\Profile\Data\Definitions\MarketingCampaigns;
use App\Package\Profile\Data\Definitions\MarketingDeliverability;
use App\Package\Profile\Data\Definitions\NearlyImpression;
use App\Package\Profile\Data\Definitions\OptIns;
use App\Package\Profile\Data\Definitions\Profile;
use App\Package\Profile\Data\Definitions\Reviews;
use App\Package\Profile\Data\Definitions\Stories;
use App\Package\Profile\Data\Definitions\Stripe;
use App\Package\Profile\Data\Definitions\Validation;
use App\Package\Profile\Data\Definitions\Website;
use App\Package\Profile\Data\Definitions\WiFiData;
use App\Package\Profile\Data\Definitions\WiFiPayments;

/**
 * Class DataDefinition
 * @package App\Package\Profile\Data
 */
class DataDefinition
{
    /**
     * @return DataDefinition
     */
    public static function base(): DataDefinition
    {
        return new DataDefinition(
            new DataArea(
                'core',
                new Profile(),
                new Interactions(),
                new OptIns(),
                new Validation()
            ),
            new DataArea(
                'wifi',
                new WiFiData(),
                new WiFiPayments(),
                new MacAddresses(),
                new NearlyImpression()
            ),
            new DataArea(
                'commerce',
                new GiftCard(),
                new Stripe(),
                new Loyalty()
            ),
            new DataArea(
                'marketing',
                new MarketingCallback(),
                new MarketingCampaigns(),
                new MarketingDeliverability(),
                new Stories()
            ),
            new DataArea(
                'features',
                new Reviews(),
                new Website()
            )
        );
    }

    /**
     * @var DataArea[] $areas
     */
    private $areas = [];

    /**
     * DataDefinition constructor.
     * @param DataArea[] $areas
     */
    public function __construct(DataArea ...$areas)
    {
        foreach ($areas as $area) {
            $this->registerArea($area);
        }
    }

    /**
     * @param callable $walkFn
     */
    public function walkReverse(callable $walkFn)
    {
        foreach (array_reverse($this->areas) as $area) {
            foreach (array_reverse($area->getObjectDefinitions()) as $objectDefinition) {
                $walkFn($area, $objectDefinition);
            }
        }
    }

    /**
     * @return DataArea[]
     */
    public function areas(): array
    {
        return $this->areas;
    }

    /**
     * @param DataArea $area
     * @return DataDefinition
     */
    private function registerArea(DataArea $area): DataDefinition
    {
        $this->areas[$area->name()] = $area;
        return $this;
    }
}