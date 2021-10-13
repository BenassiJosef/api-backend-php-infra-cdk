<?php

namespace App\Models\Integrations\ChargeBee;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriptions
 *
 * @ORM\Table(name="subscriptions", indexes={
 *     @ORM\Index(name="customerId", columns={"customerId"}),
 *     @ORM\Index(name="subscriptionId", columns={"subscriptionId"})
 * })
 * @ORM\Entity
 */
class Subscriptions
{

    static $chargeBeeToORMList = [
        'starter'              => 'starter',
        'all-in'               => 'allIn',
        'reviews'              => 'reviews',
        'marketing-automation' => 'marketingAutomation',
        'custom-integration'   => 'customIntegration',
        'content-filter'       => 'contentFilter',
        'stories'              => 'stories'
    ];

    static $legacyPlanList = [
        'lite',
        'medium',
        'premium'
    ];

    static $currentPlanList = [
        'starter',
        'allIn'
    ];

    static $currentPlanListChargeBee = [
        'starter',
        'starter_an',
        'all-in',
        'all-in_an',
        'demo'
    ];

    static $currentAddOns = [
        'content-filter',
        'content-filter_an',
        'reviews',
        'reviews_an',
        'marketing-automation',
        'marketing-automation_an',
        'custom-integration',
        'custom-integration_an',
        'stories',
        'stories_an'
    ];

    const QUANTITY_BASED_ADD_ONS = [
        'reviews',
        'reviews_an',
        'marketing-automation',
        'marketing-automation_an'
    ];

    static $legacyMarketingPlanList = [
        'marketing-small',
        'marketing-medium',
        'marketing-large',
        'enterprise-marketing-small',
        'enterprise-marketing-medium',
        'enterprise-marketing-large',
        'reviews'
    ];

    static $infrastructurePlanList = [
        'no-plan',
        'unifi-ap-hosting',
        'unifi-hosting',
        '3rd-party-integration'
    ];

    static $bespokePlanList = [
        'GH_loc',
        'GH_enterprise',
        'GH_loc2'
    ];

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="subscriptionId", nullable=false)
     */
    private $subscription_id;

    /**
     * @var string
     * @ORM\Column(name="serial", length=12, nullable=false)
     */
    private $serial;

    /**
     * @var integer
     *
     * @ORM\Column(name="billingCycles", type="integer")
     */
    private $billing_cycles;

    /**
     * @var integer
     *
     * @ORM\Column(name="billingPeriod", type="integer")
     */
    private $billing_period;

    /**
     * @var string
     *
     * @ORM\Column(name="billingPeriodUnit", type="string")
     */
    private $billing_period_unit;

    /**
     * @var string
     *
     * @ORM\Column(name="customerId", type="string")
     */
    private $customer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="currencyCode", type="string")
     */
    private $currency_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="currentTermStart", type="integer")
     */
    private $current_term_start;

    /**
     * @var integer
     *
     * @ORM\Column(name="currentTermEnd", type="integer")
     */
    private $current_term_end;

    /**
     * @var string
     *
     * @ORM\Column(name="planId", type="string")
     */
    private $plan_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="planFreeQuantity", type="integer")
     */
    private $plan_free_quantity;

    /**
     * @var integer
     *
     * @ORM\Column(name="planQuantity", type="integer")
     */
    private $plan_quantity;

    /**
     * @var integer
     *
     * @ORM\Column(name="planUnitPrice", type="integer")
     */
    private $plan_unit_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="setupFee", type="integer")
     */
    private $setup_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="startDate", type="integer")
     */
    private $start_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="trialStart", type="integer")
     */
    private $trial_start;

    /**
     * @var integer
     *
     * @ORM\Column(name="trialEnd", type="integer")
     */
    private $trial_end;

    /**
     * @var boolean
     *
     * @ORM\Column(name="autoCollection", type="boolean")
     */
    private $auto_collection;

    /**
     * @var integer
     *
     * @ORM\Column(name="termsToChange", type="integer")
     */
    private $terms_to_change;

    /**
     * @var string
     *
     * @ORM\Column(name="referralDetails", type="string")
     */
    private $referral_details;

    /**
     * @var string
     *
     * @ORM\Column(name="poNumber", type="string")
     */
    private $po_number;

    /**
     * @var string
     *
     * @ORM\Column(name="paymentSourceId", type="string")
     */
    private $payment_source_id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoiceNotes", type="string")
     */
    private $invoice_notes;

    /**
     * @var string
     *
     * @ORM\Column(name="metaData", type="json_array")
     */
    private $meta_data;

    /**
     * @var boolean
     *
     * @ORM\Column(name="invoiceImmediately", type="boolean")
     */
    private $invoice_immediately;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="nextBillingAt", type="integer")
     */
    private $next_billing_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="remainingBillingCycles", type="integer")
     */
    private $remaining_billing_cycles;

    /**
     * @var integer
     *
     * @ORM\Column(name="createdAt", type="integer")
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="startedAt", type="integer")
     */
    private $started_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="activatedAt", type="integer")
     */
    private $activated_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="cancelledAt", type="integer")
     */
    private $cancelled_at;

    /**
     * @var string
     *
     * @ORM\Column(name="cancelReason", type="string")
     */
    private $cancel_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="affiliateToken", type="string")
     */
    private $affiliate_token;

    /**
     * @var string
     *
     * @ORM\Column(name="createdFromIp", type="string")
     */
    private $created_from_ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="updatedAt", type="integer")
     */
    private $updated_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hasScheduledChanges", type="boolean")
     */
    private $has_scheduled_changes;

    /**
     * @var integer
     *
     * @ORM\Column(name="dueInvoicesCount", type="integer")
     */
    private $due_invoices_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="dueSince", type="integer")
     */
    private $due_since;

    /**
     * @var integer
     *
     * @ORM\Column(name="totalDues", type="integer")
     */
    private $total_dues;

    /**
     * @var string
     *
     * @ORM\Column(name="baseCurrencyCode", type="string")
     */
    private $base_currency_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="mrr", type="integer")
     */
    private $mrr;

    /**
     * @var integer
     *
     * @ORM\Column(name="exchangeRate", type="bigint")
     */
    private $exchange_rate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * Get array copy of object
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

}

