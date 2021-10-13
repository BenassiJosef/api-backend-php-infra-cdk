<?php

/**
 * Created by jamieaitken on 08/02/2019 at 14:49
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Billing\Organisation;

use App\Package\Billing\Subscription;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use Slim\Http\Request;

class SubscriptionsRequest
{

    protected $sources = [
        'britvic' => 30,
        'marketing' => 30,
        'gifting' => 90,
    ];

    public $plan = null;
    public $currency = null;
    public $annual = true;
    public $venues = 0;
    public $contacts = 0;
    public $addons = [];
    public $trial = false;
    public $source = null;

    public function __construct(
        Request $request
    ) {
        $this->plan = $request->getParsedBodyParam('plan');
        $this->currency = $request->getParsedBodyParam('currency', 'GBP');
        $this->annual = $request->getParsedBodyParam('annual', true);
        $this->venues = (int) $request->getParsedBodyParam('venues', 0);
        $this->contacts = (int) $request->getParsedBodyParam('contacts', 0);
        $this->addons = (array) $request->getParsedBodyParam('addons', []);
        $this->trial =  $request->getParsedBodyParam('trial', false);
        $this->source =  $request->getParsedBodyParam('source');
    }


    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAnnual(): bool
    {
        return $this->annual;
    }
    public function getTrial(): bool
    {
        return $this->trial;
    }
    public function getVenues(): int
    {
        return $this->venues;
    }
    public function getContacts(): int
    {
        return $this->contacts;
    }
    public function getAddons(): array
    {
        return $this->addons;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getSubscription(): Subscription
    {
        $subscription = new Subscription(
            $this->getPlan(),
            $this->getAnnual(),
            $this->getCurrency(),
            $this->getAddons(),
            $this->getTrial()
        );

        if (!is_null($this->getSource())) {
            if (array_key_exists($this->getSource(), $this->sources)) {
                $subscription->setTrialEnd($this->sources[$this->getSource()]);
            }
        }

        return $subscription;
    }
}
