<?php


namespace App\Package\Billing;

use App\Models\Billing\Organisation\Subscriptions;
use App\Models\Organization;
use App\Package\Billing\ChargebeeAPI;
use DateTime;

class Subscription
{

	public $plans = [
		'free' => [
			'contacts' => 5000,
			'venues' => 0
		],
		'starter' => [
			'contacts' => 6000,
			'venues' => 1
		],
		'growth' => [
			'contacts' => 10000,
			'venues' => 10
		],
		'enterprise' => [
			'contacts' => 150000,
			'venues' => 60
		]
	];

	protected $chargebee;

	public $contactLimit = 0;
	public $venueLimit = 0;

	public $currency = 'GBP';
	public $planId = 'starter';
	public $annual = true;
	public $addons = [];
	public $trial = false;
	public $trial_end = '';

	public function __construct(string $planId, bool $annual, string $currency, array $addons, ?bool $trial = false)
	{
		$this->chargebee =  new ChargebeeAPI();
		$this->annual = $annual;
		$this->planId = $planId;
		$this->currency = $currency;
		$this->trial = $trial;
		$this->trial_end = new DateTime();
		if ($this->planId === 'starter') {
			$this->addons = $addons;
		}
		if ($this->trial) {
			$this->setTrialEnd(14);
		}

		$this->getPlanDetails($planId);
	}

	public function setTrialEnd(int $days)
	{
		$now               = new \DateTime();
		$twoWeeksFromNow   = $now->modify('+' . $days . ' days');
		$this->trial_end = $twoWeeksFromNow->getTimestamp();
	}


	public function getAddons(int $venues, int $contacts)
	{
		$addons = [];

		$extraVenues = $this->getExtraVenues($venues);

		$extraContacts = $this->getExtraContacts($contacts);
		if ($extraVenues !== 0) {
			$addons[] = [
				'id' => $this->getChargebeeId('venues'),
				'quantity' => $extraVenues
			];
		}
		if ($extraContacts !== 0) {
			$addons[] = [
				'id' => $this->getChargebeeId('contacts'),
				'quantity' => $extraContacts
			];
		}

		foreach ($this->addons as $addon) {
			$addons[] = [
				'id' => $this->getChargebeeId($addon)
			];
		}

		return $addons;
	}

	public function updateSubscription(string $subscriptionId, int $venues, int $contacts, string $orgId)
	{
		return $this->chargebee->updateSubscription(
			$subscriptionId,
			[
				'payload' => [
					"planId" => $this->getChargebeeId($this->planId),
					"addons" => $this->getAddons($venues, $contacts),
					"cf_organisation_id" => $orgId
				]
			]
		);
	}

	public function createSubscription(string $customerId, int $venues, int $contacts, string $orgId)
	{
		return $this->chargebee->createSubscription($customerId, [
			'payload' => [
				"trial" => $this->trial,
				"trial_end" => $this->trial_end,
				"planId" => $this->getChargebeeId($this->planId),
				"addons" => $this->getAddons($venues, $contacts),
				"cf_organisation_id" => $orgId
			]
		]);
	}

	public function createHostedPagePayload(int $venues, int $contacts, Organization $customerOrganisation)
	{
		return [
			'customer'        => [
				'id'                 => $customerOrganisation->getChargebeeCustomerId(),
				'cf_organisation_id' => $customerOrganisation->getId()->toString(),
			],
			"subscription" => [
				"planId" => $this->getChargebeeId($this->planId),
				"cf_organisation_id" => $customerOrganisation->getId()->toString()
			],
			"addons" => $this->getAddons($venues, $contacts)
		];
	}

	public function createSmsCreditInvoice(int $credits, Subscriptions $sub)
	{
		return $this->chargebee->addCredits([
			'customerId'        => $sub->getOrganisation()->getChargebeeCustomerId(),
			'currencyCode' => $sub->getCurrency(),
			"addons" => [
				[
					'id' => strtolower('sms-credits-' . $this->currency),
					'quantity' => $credits
				]
			]
		]);
	}

	public function createSubscriptionHostedPage(int $venues, int $contacts, Organization $customerOrganisation)
	{
		return $this->chargebee->hostedNewPage($this->createHostedPagePayload(
			$venues,
			$contacts,
			$customerOrganisation
		));
	}

	public function updateInProductSubscription(string $subscriptionId, int $venues, int $contacts)
	{
		return $this->chargebee->hostedPage([
			"subscription" => [
				"id" => $subscriptionId,
				"planId" => $this->getChargebeeId($this->planId),
			],
			"addons" => $this->getAddons($venues, $contacts)
		]);
	}

	public function getSubscription(string $subscriptionId)
	{
		return $this->chargebee->getSubscription($subscriptionId);
	}

	public function getChargebeeId(string $planId)
	{
		if ($planId === 'free') {
			return $planId;
		}
		$annualString = $this->annual === true ? '-annual' : '';
		return strtolower($planId . $annualString . '-' . $this->currency);
	}

	public function getExtraVenues(int $venues): int
	{
		if ($venues > $this->venueLimit) {
			return ($venues - $this->venueLimit);
		}
		return 0;
	}

	public function getExtraContacts(int $contacts): int
	{
		if ($contacts > $this->contactLimit) {
			return ($contacts - $this->contactLimit) / 1000;
		}
		return 0;
	}

	public function getPlanDetails(string $planId)
	{
		$plan = $this->plans[$planId];
		$this->venueLimit = $plan['venues'];
		$this->contactLimit = $plan['contacts'];
		return $plan;
	}

	public function createChargeBeeCustomer(ChargebeeCustomer $customer)
	{
		return $this->chargebee->createCustomer($customer);
	}
}
