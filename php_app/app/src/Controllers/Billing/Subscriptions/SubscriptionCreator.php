<?php
/**
 * Created by jamieaitken on 06/03/2018 at 10:56
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Billing\Subscriptions;


use App\Models\Organization;

interface SubscriptionCreator
{
    public function createSubscription(Organization $customerOrganisation, array $body);
}