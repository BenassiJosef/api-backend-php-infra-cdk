<?php

/**
 * Created by jamieaitken on 06/03/2018 at 10:46
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Billing\Subscriptions;

use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeSubscriptionController;
use App\Controllers\Integrations\ChargeBee\ChargeBeeHostedPageController;
use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Locations\Creation\LocationCreationFactory;
use App\Controllers\Members\CustomerPricingController;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Package\Billing\ChargebeeCustomer;
use App\Package\Organisations\OrganizationService;
use App\Package\RequestUser\UserProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;

class LocationSubscriptionController implements SubscriptionCreator
{

	protected $sources = [
		'britvic' => 30,
		'gifting' => 90
	];

	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * @var UserProvider
	 */
	private $userProvider;

	protected $em;
	protected $chargebeeSubscription;
	protected $connectCache;
	protected $mp;
	protected $customerPricing;
	protected $isAnnualSubscription = false;
	protected $hasAddOns            = false;
	protected $hasTrial             = false;

	/**
	 * @var _ChargeBeeCustomerController $chargebeeCustomerController
	 */
	private $chargebeeCustomerController;

	public function __construct(
		EntityManager $em,
		OrganizationService $organizationService,
		UserProvider $userProvider,
		_ChargeBeeCustomerController $chargebeeCustomerController = null
	) {
		$this->em                    = $em;
		$this->chargebeeSubscription = new _ChargeBeeSubscriptionController();
		$this->customerPricing       = new CustomerPricingController($this->em);
		$this->connectCache          = new CacheEngine(getenv('CONNECT_REDIS'));
		$this->mp                    = new _Mixpanel();
		$this->organizationService   = $organizationService;
		$this->userProvider          = $userProvider;
		if ($chargebeeCustomerController === null) {
			$chargebeeCustomerController = new _ChargeBeeCustomerController($em);
		}
		$this->chargebeeCustomerController = $chargebeeCustomerController;
	}

	public function createSubscriptionRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody();
		$user = $this->userProvider->getOauthUser($request);

		$organization = $this->getOrganizationForBody($body, $user);
		if ($organization->getChargebeeCustomerId() === null) {
			$chargebeeCustomer = new ChargebeeCustomer($user);
			$this->chargebeeCustomerController->createChargebeeCustomer($chargebeeCustomer);
			$organization->setChargebeeCustomerId($chargebeeCustomer->getId());
		}
		$this->em->persist($user);
		$this->em->persist($organization);
		$this->em->flush();
		$send = $this->createSubscription($organization, $body);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	/**
	 * @param array $body
	 * @param OauthUser $user
	 * @return Organization
	 * @throws ORMException
	 */
	private function getOrganizationForBody(array $body, OauthUser $user): Organization
	{
		if (isset($body['orgId'])) {
			return $this->organizationService->getOrganisationById($body['orgId']);
		}
		return $this
			->organizationService
			->getOrCreateOrganizationForOwnerId(
				Uuid::fromString($user->getUid()),
				$body['company']
			);
	}

	public function updateFromWebHook(array $subscriptionEvent, string $state)
	{
		$subscription = $this->em->getRepository(Subscriptions::class)->findOneBy(
			[
				'subscription_id' => $subscriptionEvent['id']
			]
		);

		$subscriptionAddOns = $this->em->getRepository(SubscriptionsAddon::class)->findBy(
			[
				'subscription_id' => $subscriptionEvent['id']
			]
		);


		if (is_null($subscription)) {
			$subscription                  = new Subscriptions();
			$subscription->subscription_id = $subscriptionEvent['id'];
		}

		$subscriptionKeys = array_keys($subscription->getArrayCopy());

		foreach ($subscriptionEvent as $key => $value) {
			if (in_array($key, $subscriptionKeys) && $key !== 'id') {
				$subscription->$key = $value;
			}
		}

		if (isset($subscriptionEvent['cf_serial'])) {
			$subscription->serial = $subscriptionEvent['cf_serial'];
		}

		foreach ($subscriptionAddOns as $addOn) {
			$this->em->remove($addOn);
		}
		$this->em->flush();


		if (isset($subscriptionEvent['addons'])) {
			foreach ($subscriptionEvent['addons'] as $key => $addon) {
				$addOn = new SubscriptionsAddon(
					$subscriptionEvent['id'],
					$addon['id'],
					$addon['quantity'],
					$addon['unit_price']
				);
				$this->em->persist($addOn);
			}
		}

		$this->em->persist($subscription);
		$this->em->flush();
	}


	private function isAnnual(array $body)
	{
		if (isset($body['annually'])) {
			return $this->isAnnualSubscription = $body['annually'];
		}

		return false;
	}


	/**
	 * @param Organisation $customerOrganisation
	 * @param array $body
	 * {
	 *    "trial": true, // is it a trial
	 *    "planId": "all-in", // what's the plan?
	 *    "hosted": true, // what does this mean? - always true
	 *    "method": {
	 *       "name": "unifi" // wifi vendor
	 *       "serial": "optional serial" // the serial (only for mikrotic? maybe)
	 *    },
	 *    "addons": [] // array of things? may not be an array
	 * }
	 * @return array
	 * @throws \Exception
	 */
	public function createSubscription(Organization $customerOrganisation, array $body)
	{
		if (!isset($body['method'])) {
			return Http::status(404, 'METHOD_MISSING');
		}

		if (!isset($body['method']['name'])) {
			return Http::status(404, 'NAME_MISSING');
		}

		if (!in_array($body['planId'], Subscriptions::$currentPlanListChargeBee)) {
			return Http::status(409, 'INVALID_PLAN');
		}


		$locationCreationFactory = new LocationCreationFactory(
			$this->em,
			$body['method']['name'],
			isset($body['method']['serial']) ? strtoupper($body['method']['serial']) : null
		);

		$method = $locationCreationFactory->getInstance();

		if (is_null($method->getSerial())) {

			if ($method instanceof MikrotikCreationController) {
				return Http::status(409, 'MIKROTIK_SERIAL_IS_EMPTY');
			}

			$method->setSerial($method::serialGenerator());
		} elseif (!$method->locationCreationChecksController->executePreCreationChecks()) {
			return Http::status(409, $method->locationCreationChecksController->getReasonForFailure());
		}

		$addOns   = null;
		$annual   = $this->isAnnual($body);
		$basePlan = $body['planId'];

		$body['trial_end'] = 0;

		if (isset($body['addOns'])) {
			$addOns = [$body['addOns']];
		}

		if (strpos($basePlan, 'all-in') !== false) {
			$addOns = null;
		}


		if ($annual === true) {
			$basePlan = $basePlan . '_an';
			if (!is_null($addOns)) {
				foreach ($addOns as $addOn => $addOnList) {
					foreach ($addOnList as $key => $value) {
						if (isset($value['id'])) {
							$addOns[$addOn][$key]['id'] = $addOns[$addOn][$key]['id'] . '_an';
						}
					}
				}
			}
		}

		if (isset($body['trial'])) {
			$duration = 14;
			if (isset($body['source'])) {
				if (array_key_exists($body['source'], $this->sources)) {
					$duration = $this->sources[$body['source']];
				}
			}
			if ($body['trial'] === true) {
				$now               = new \DateTime();
				$twoWeeksFromNow   = $now->modify('+' . $duration . ' days');
				$body['trial_end'] = $twoWeeksFromNow->getTimestamp();
			}
		}

		$requestArray = [
			'customer'        => [
				'id'                 => $customerOrganisation->getChargebeeCustomerId(),
				'cf_organisation_id' => $customerOrganisation->getId()->toString(),
			],
			'subscription'    => [
				'planId'             => $basePlan,
				'cf_serial'          => $method->getSerial(),
				'cf_organisation_id' => $customerOrganisation->getId()->toString(),
				'trialEnd'           => $body['trial_end']
			],
			'passThruContent' => $method->getSerial(),
			'embed'           => false
		];

		if (isset($body['redirectUrl'])) {
			$requestArray['redirectUrl'] = $body['redirectUrl'];
		}

		if (isset($body['embed'])) {
			$requestArray['embed'] = $body['embed'];
		}

		$requestArray['subscription']['cf_method'] = $method->getVendorForChargeBee();

		$getCustomerPricing = $this->customerPricing->findOrCreatePricing($customerOrganisation->getId()->toString())->getArrayCopy();

		if (!is_null($addOns)) {
			foreach ($addOns as $addOn => $addOnList) {
				foreach ($addOnList as $key => $value) {
					if ($annual) {
						$nonAnnual                         = str_replace('_an', '', $value['id']);
						$addOns[$addOn][$key]['unitPrice'] = ($getCustomerPricing[Subscriptions::$chargeBeeToORMList[$nonAnnual]] * 12) * 0.9;
					} else {
						$addOns[$addOn][$key]['unitPrice'] = $getCustomerPricing[Subscriptions::$chargeBeeToORMList[$value['id']]];
					}
				}
			}
			$requestArray['addons'] = $addOns;
		}

		if ($annual === true) {
			$requestArray['subscription']['planUnitPrice'] = ($getCustomerPricing[Subscriptions::$chargeBeeToORMList[str_replace(
				'_an',
				'',
				$basePlan
			)]] * 12) * 0.9;
		} elseif (strpos($requestArray['subscription']['planId'], 'demo') !== false) {
		} else {
			$requestArray['subscription']['planUnitPrice'] = $getCustomerPricing[Subscriptions::$chargeBeeToORMList[$basePlan]];
		}

		if (!isset($body['hosted'])) {
			$hostedPageController = new ChargeBeeHostedPageController();
			$hostedPage           = $hostedPageController->createNewSubscription($requestArray);
		} else {
			$hostedPageController = new _ChargeBeeSubscriptionController();
			foreach ($requestArray['subscription'] as $key => $value) {
				$requestArray[$key] = $value;
			}
			$hostedPage = $hostedPageController->createSubscription($requestArray);
		}


		return Http::status(200, $hostedPage['message']);
	}

	public function updateSubscriptionRoute(Request $request, Response $response)
	{
		$body           = $request->getParsedBody();
		$subscriptionId = $request->getAttribute('id');
		$user           = $request->getAttribute('accessUser');
		$send           = $this->updateSubscription($body, $subscriptionId, $user);
		$this->em->clear();
		return $response->withJson($send, $send['status']);
	}

	public function updateSubscription(array $body, string $subscriptionId, array $user)
	{
		$chargeBeeSubId = $this->em->getRepository(Subscriptions::class)->findOneBy(
			[
				'serial' => $subscriptionId
			]
		);

		if (is_null($chargeBeeSubId)) {
			return Http::status(204);
		}

		$addons               = null;
		$isToBeBilledAnnually = $this->isAnnual($body);

		if (isset($body['addOns'])) {
			$addons = [$body['addOns']];
		}

		$requestArray = [
			'subscription'     => [
				'id'     => $chargeBeeSubId->subscription_id,
				'planId' => $body['planId']
			],
			'replaceAddonList' => true,
			'embed'            => true,
			'passThruContent'  => $subscriptionId
		];

		$pricing = $this->customerPricing->getPricing($body['orgId'])['message'];


		if ($isToBeBilledAnnually === true) {
			$requestArray['subscription']['planUnitPrice'] = ($pricing[str_replace(
				'_an',
				'',
				$body['planId']
			)] * 12) * 0.9;
			$requestArray['subscription']['planId']        .= '_an';

			if (!is_null($addons)) {
				foreach ($addons as $addOn => $addOnList) {
					foreach ($addOnList as $key => $value) {
						if (isset($value['id'])) {
							$addons[$addOn][$key]['unitPrice'] = ($pricing[Subscriptions::$chargeBeeToORMList[$value['id']]] * 12) * 0.9;
							$addons[$addOn][$key]['id']        = $addons[$addOn][$key]['id'] . '_an';
						}
					}
				}
				$requestArray['addons'] = $addons;
			}
		} else {
			$requestArray['subscription']['planUnitPrice'] = $pricing[$body['planId']];

			if (!is_null($addons)) {
				foreach ($addons as $addOn => $addOnList) {
					foreach ($addOnList as $key => $value) {
						if (isset($value['id'])) {
							$addons[$addOn][$key]['unitPrice'] = $pricing[Subscriptions::$chargeBeeToORMList[$value['id']]];
						}
					}
				}

				$requestArray['addons'] = $addons;
			}
		}


		$newHostedPage = new ChargeBeeHostedPageController();
		$hostedUrl     = $newHostedPage->updateExistingSubscription($requestArray);
		if ($hostedUrl['status'] !== 200) {
			return $hostedUrl;
		}

		return Http::status(200, $hostedUrl['message']);
	}
}
