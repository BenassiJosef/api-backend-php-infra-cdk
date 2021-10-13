<?php

/**
 * Created by jamieaitken on 08/02/2019 at 14:49
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Billing\Organisation;

use App\Models\Organization;
use DateTime;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Quotes
 *
 * @ORM\Table(name="organization_subscription")
 * @ORM\Entity
 */
class Subscriptions implements JsonSerializable
{
    const ADDON_WEB_FORMS         = 'webforms';
    const ADDON_MARKETING         = 'marketing';
    const ADDON_DATA_IMPORT       = 'dataimport';
    const ADDON_MOBILE_APP        = 'mobileapp';
    const ADDON_WEBSITE_FOOTPRINT = 'websitefootprint';

    const ADDON_ANALYTICS           = 'analytics';
    const ADDON_WIFI                = 'wifi';
    const ADDON_EMAIL_SUPPORT       = 'emailsupport';
    const ADDON_SIGN_IN             = 'signin';
    const ADDON_CUSTOM_DATA_CAPTURE = 'customdatacapture';

    const ADDON_GIFT_CARDS           = 'giftcards';
    const ADDON_STORIES              = 'stories';
    const ADDON_REVIEWS              = 'reviews';
    const ADDON_PHONE_SUPPORT        = 'phonesupport';
    const ADDON_ZAPIER               = 'zapier';
    const ADDON_MARKETING_AUTOMATION = 'marketingautomation';
    const ADDON_LOYALTY = 'loyalty';

    const PLAN_FREE           = 'free';
    const PLAN_STARTER        = 'starter';
    const PLAN_GROWTH         = 'growth';
    const PLAN_ENTERPRISE     = 'enterprise';
    const PLAN_LEGACY_STARTER = 'starter';
    const PLAN_LEGACY_ALL     = 'all';

    private static $legacyAllAddons = [
        self::ADDON_MARKETING,
        self::ADDON_MOBILE_APP,
        self::ADDON_ANALYTICS,
        self::ADDON_WIFI,
        self::ADDON_SIGN_IN,
        self::ADDON_CUSTOM_DATA_CAPTURE,
        self::ADDON_STORIES,
        self::ADDON_REVIEWS,
        self::ADDON_PHONE_SUPPORT,
        self::ADDON_ZAPIER,
        self::ADDON_MARKETING_AUTOMATION,
    ];

    private static $legacyStarterAddons = [
        self::ADDON_MOBILE_APP,
        self::ADDON_ANALYTICS,
        self::ADDON_WIFI,
        self::ADDON_SIGN_IN,
        self::ADDON_CUSTOM_DATA_CAPTURE,
        self::ADDON_PHONE_SUPPORT,
        self::ADDON_ZAPIER,
    ];

    protected $freeAddons    = [
        self::ADDON_WEB_FORMS,
        self::ADDON_MARKETING,
        self::ADDON_DATA_IMPORT,
        self::ADDON_MOBILE_APP,
        self::ADDON_WEBSITE_FOOTPRINT,
    ];
    protected $starterAddons = [
        self::ADDON_ANALYTICS,
        self::ADDON_WIFI,
        self::ADDON_EMAIL_SUPPORT,
        self::ADDON_SIGN_IN,
        self::ADDON_CUSTOM_DATA_CAPTURE,
    ];
    protected $growthAddons  = [
        self::ADDON_GIFT_CARDS,
        self::ADDON_STORIES,
        self::ADDON_REVIEWS,
        self::ADDON_PHONE_SUPPORT,
        self::ADDON_ZAPIER,
        self::ADDON_MARKETING_AUTOMATION,
        self::ADDON_LOYALTY
    ];

    public function __construct(
        Organization $organization,
        string $subscriptionId,
        array $addons,
        int $contacts,
        int $venues,
        string $plan,
        string $currency,
        string $status,
        bool $annual
    ) {
        $this->organizationId = $organization->getId();
        $this->organization   = $organization;
        $this->subscriptionId = $subscriptionId;
        $this->addons         = $addons;
        $this->venues         = $venues;
        $this->contacts       = $contacts;
        $this->plan           = $plan;
        $this->currency       = $currency;
        $this->status         = $status;
        $this->annual         = $annual;
        $this->createdAt      = new DateTime();
        $this->legacy = false;
    }

    /**
     * @ORM\Id
     * @ORM\Column(name="organization_id", type="uuid")
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\OneToOne(targetEntity="App\Models\Organization", mappedBy="parent")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var string
     * @ORM\Column(name="subscription_id", type="string")
     */
    private $subscriptionId;

    /**
     * @var array
     * @ORM\Column(name="addons", type="json_array")
     */
    private $addons;

    /**
     * @var string
     * @ORM\Column(name="venues", type="integer")
     */
    private $venues;


    /**
     * @var string
     * @ORM\Column(name="contacts", type="integer")
     */
    private $contacts;

    /**
     * @var string
     * @ORM\Column(name="plan", type="string")
     */
    private $plan;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    /**
     * @var string
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var bool
     * @ORM\Column(name="annual", type="boolean")
     */
    private $annual;

    /**
     * @var DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var bool
     * @ORM\Column(name="legacy", type="boolean")
     */
    private $legacy;

    public function getIncludedSmsCredits()
    {
        $creditsToAdd = $this->getContacts() * 0.1;
        if ($this->getAnnual()) {
            return $creditsToAdd * 12;
        }
        return $creditsToAdd;
    }


    public function getOrganisationId()
    {
        return $this->organizationId;
    }

    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(string $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this->subscriptionId;
    }

    public function chargeBeeAddons()
    {
        return $this->addons;
    }

    /**
     * @param mixed ...$addonsRequest
     * @return bool
     */
    public function hasAddon(...$addonsRequest): bool
    {
        $addons = $this->getAddons();
        foreach ($addonsRequest as $addon) {
            if (!array_key_exists($addon, $addons)) {
                return false;
            }
            $hasAddon = $addons[$addon];
            if (!$hasAddon) {
                return false;
            }
        }
        return true;
    }

    public function getAddons()
    {
        switch ($this->getPlan()) {
            case self::PLAN_FREE:
                return $this->formatAddons($this->freeAddons);
                break;
            case self::PLAN_STARTER:
                return $this->formatAddons(
                    array_merge(
                        $this->freeAddons,
                        $this->starterAddons,
                        $this->addons
                    )
                );
                break;
            case self::PLAN_GROWTH:
            case self::PLAN_ENTERPRISE:
                return $this->formatAddons(
                    array_merge(
                        $this->freeAddons,
                        $this->starterAddons,
                        $this->growthAddons,
                        $this->addons
                    )
                );
                break;
            case self::PLAN_LEGACY_STARTER;
                return $this->formatAddons(array_merge(self::$legacyStarterAddons, $this->addons));
                break;
            case self::PLAN_LEGACY_ALL:
                return $this->formatAddons(array_merge(self::$legacyAllAddons, $this->addons));
                break;
            default:
                return $this->formatAddons($this->addons);
        }
    }

    public function formatAddons(array $addons)
    {
        $hasAddons = [];
        $allAddons = array_merge(
            $this->freeAddons,
            $this->starterAddons,
            $this->growthAddons
        );
        foreach ($allAddons as $addon) {
            $hasAddons[$addon] = false;
        }
        foreach ($addons as $addon) {
            $hasAddons[$addon] = true;
        }
        return $hasAddons;
    }

    public function setAddons(array $addons)
    {
        $this->addons = $addons;
        return $this->addons;
    }

    public function getPlan()
    {
        return $this->plan;
    }

    public function setPlan(string $plan)
    {
        $this->plan = $plan;
        return $this->plan;
    }


    public function getContacts()
    {
        $contacts = $this->contacts;
        switch ($this->getPlan()) {
            case self::PLAN_STARTER:
                $contacts += 6;
                break;
            case self::PLAN_GROWTH:
                $contacts += 10;
                break;
            case self::PLAN_ENTERPRISE:
                $contacts += 150;
                break;
        }
        return $contacts * 1000;
    }

    public function setContacts(int $contacts)
    {
        $this->contacts = $contacts;
        return $this->contacts;
    }

    public function getChargeBeeVenues()
    {
        return $this->venues;
    }

    public function getVenues()
    {
        $venues = $this->venues;
        switch ($this->getPlan()) {
            case self::PLAN_STARTER:
                $venues += 1;
                break;
            case self::PLAN_GROWTH:
                $venues += 10;
                break;
            case self::PLAN_ENTERPRISE:
                $venues += 60;
                break;
        }
        return $venues;
    }

    public function setVenues(int $venues)
    {
        $this->venues = $venues;
        return $this->venues;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this->currency;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this->status;
    }

    public function getAnnual()
    {
        return $this->annual;
    }

    public function setAnnual(bool $annual)
    {
        $this->annual = $annual;
        return $this->annual;
    }

    public function isSubscriptionValid(): bool
    {

        if (
            $this->getStatus() === 'active' ||
            $this->getStatus() === 'in_trial'
            || $this->getStatus() === 'future'
        ) {
            return true;
        }

        return false;
    }

    public function getOrganisation(): Organization
    {
        return $this->organization;
    }

    public function isLegacy(): bool
    {
        return $this->legacy;
    }

    public function jsonSerialize()
    {
        return [
            "organization_id" => $this->getOrganisationId(),
            "subscription_id" => $this->getSubscriptionId(),
            "addons"          => $this->getAddons(),
            "plan"            => $this->getPlan(),
            "createdAt"       => $this->createdAt,
            "contacts"        => $this->getContacts(),
            "venues"          => $this->getVenues(),
            "currency"        => $this->getCurrency(),
            "status"          => $this->getStatus(),
            "annual"          => $this->getAnnual()
        ];
    }
}
