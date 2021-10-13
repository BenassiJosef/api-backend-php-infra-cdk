<?php
/**
 * Created by jamieaitken on 22/01/2019 at 13:55
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\LocationSearch;


use App\Controllers\Locations\LocationSearch\ILocationSearch;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Models\Locations\LocationSettings;
use App\Models\NetworkAccess;

class AccessibleMarketLocationSearcher extends SearchableLocation
{
    public function prepareBaseStatement()
    {
        $sqlStatement = 'ns.serial, ns.alias';

        return $this->entityManager->createQueryBuilder()
            ->select($sqlStatement)
            ->from(LocationSettings::class, 'ns')
            ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'ns.serial = na.serial')
            ->leftJoin(Subscriptions::class, 'su', 'WITH', 'na.serial = su.serial')
            ->leftJoin(SubscriptionsAddon::class, 'sa', 'WITH', 'su.subscription_id = sa.subscription_id')
            ->where('na.serial IN (:serials)')
            ->andWhere('su.status IN (:activeStatus)')
            ->andWhere('su.plan_id IN (:allInPlans) OR sa.add_on_id IN (:marketingPlans)')
            ->setParameter('serials', $this->getSerials())
            ->setParameter('activeStatus', ['active', 'in_trial'])
            ->setParameter('allInPlans', ['all-in', 'all-in_an'])
            ->setParameter('marketingPlans', ['marketing-automation', 'marketing-automation_an'])
            ->groupBy('ns.serial');
    }
}