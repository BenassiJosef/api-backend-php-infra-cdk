<?php

/**
 * Created by jamieaitken on 08/03/2018 at 14:58
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations;

class WalledGardenWhitelist
{

    private $facebook = [
        'facebook.com',
        'facebook.net',
        'akamaihd.net',
        'fbcdn.net',
        'connect.facebook.net',
        'akamai.net',
        'tfbnw.net',
        'atdmt.com',
        'fbsbx.com'
    ];

    private $stripe = [
        'checkout.stripe.com',
        'js.stripe.com',
        'stripecdn.com',
        'api.stripe.com',
        'm.stripe.com',
        'q.stripe.com'
    ];

    private $paypal = [
        'paypal.com',
        'paypalobjects.com'
    ];

    private $blackbxServices = [
        'nearly.online',
        'api.stampede.ai',
        'sentry.io'
    ];

    private $appleServices = [
        'appleid.cdn-apple.com',
        'appleid.apple.com'
    ];

    public function getCompleteList()
    {
        return array_merge($this->facebook, $this->stripe, $this->paypal, $this->blackbxServices, $this->appleServices);
    }

    public function getAppleBxServiceList()
    {
        return $this->appleServices;
    }

    public function getFacebookList()
    {
        return $this->facebook;
    }

    public function getStripeList()
    {
        return $this->stripe;
    }

    public function getPaypalList()
    {
        return $this->paypal;
    }

    public function getBlackBxServiceList()
    {
        return $this->blackbxServices;
    }
}
