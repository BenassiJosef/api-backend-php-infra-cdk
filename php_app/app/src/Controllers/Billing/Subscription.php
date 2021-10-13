<?php

namespace App\Controllers\Billing;

use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Locations\Creation\LocationCreationFactory;
use App\Models\Billing\Organisation\Subscriptions;
use App\Models\Billing\Organisation\SubscriptionsRequest;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Package\Organisations\OrganizationService;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Package\Billing;
use App\Package\Billing\SMSTransactions;
use App\Package\Reports\FromQuery;
use App\Package\RequestUser\UserProvider;
use App\Package\Vendors\Inform;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;

class Subscription
{
	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var UserProvider
	 */
	private $userProvider;

	/**
	 * @var SMSTransactions
	 */
	private $smsTransactions;

	/**
	 * @var CacheEngine
	 */
	private $cache;

	public function __construct(OrganizationService $organizationService, EntityManager $em)
	{
		$this->organizationService  = $organizationService;
		$this->em = $em;
		$this->userProvider = new UserProvider($this->em);
		$this->cache = new CacheEngine(getenv('CONNECT_REDIS'));
		$this->smsTransactions = new SMSTransactions($organizationService, $this->em);
	}

	public function postAddVenue(Request $request, Response $response)
	{
		$serial = $request->getParsedBodyParam('serial', null);
		$vendor = $request->getParsedBodyParam('vendor');
		$orgId = $request->getAttribute('orgId');
		$organisation = $this->organizationService->getOrganisationById($orgId);
		if (!$vendor || !$organisation) {
			return $response->withStatus(400);
		}

		$res = $this->addVenue($organisation, $vendor, $serial);

		return $response->withJson($res, $res['status']);
	}

	public function addVenue(Organization $organization, string $vendor, string $serial = null)
	{

		$organisationSubscription = $this->getOrganisationSubscription($organization->getId()->toString());

		$venues = count($organization->getLocations()->toArray()) + 1;

		if ($organisationSubscription->getVenues() < $venues) {
			$subscription = new Billing\Subscription(
				$organisationSubscription->getPlan(),
				$organisationSubscription->getAnnual(),
				$organisationSubscription->getCurrency(),
				$organisationSubscription->chargeBeeAddons(),
			);
			$updated = $subscription->updateSubscription(
				$organisationSubscription->getSubscriptionId(),
				$venues,
				$organisationSubscription->getContacts(),
				$organization->getId()->toString()
			);

			if ($updated['status'] === 200) {
				$this->webhook($updated['message']);
			} else {
				return $updated;
			}
		}

		$locationCreationFactory = new LocationCreationFactory($this->em, $vendor, $serial);
		$method = $locationCreationFactory->getInstance();

		if (is_null($method->getSerial())) {
			if ($method instanceof MikrotikCreationController) {
				return Http::status(409, 'MIKROTIK_SERIAL_IS_EMPTY');
			}
			$method->serialGenerator();
		}
		if (!$method instanceof MikrotikCreationController) {
			$inform = new Inform($this->em);
			$inform->create($method->getSerial(), $vendor);
			$method->initialiseLocationSettings($method->getSerial(), $organization);
		}

		$method->locationAccessController->assignAccess(
			$method->getSerial(),
			$organization->getOwnerId()->toString(),
			'',
			$organization
		);
		$this->cache->deleteMultiple([
			$organization->getOwnerId()->toString() . ':location:accessibleLocations',
			$organization->getOwnerId()->toString() . ':profile'
		]);

		return Http::status(200, [
			'serial' => $method->getSerial(),
			'venues' => $venues,
		]);
	}

	public function getOrganisationSubscription(string $orgId): ?Subscriptions
	{
		return $this->em->getRepository(Subscriptions::class)->findOneBy([
			'organizationId' => $orgId
		]);
	}

	public function getSubscription(Request $request, Response $response)
	{
		$orgId = $request->getAttribute('orgId');
		$organisationSubscription = $this->getOrganisationSubscription($orgId);

		$res = Http::status(404);
		if ($organisationSubscription) {
			$res = Http::status(200, $organisationSubscription->jsonSerialize());
		}


		return $response->withJson($res, $res['status']);
	}

	public function putSubscription(Request $request, Response $response)
	{

		$orgId = $request->getAttribute('orgId');
		$subscriptionId = $request->getAttribute('subscriptionId');

		$subscriptionRequest = new SubscriptionsRequest($request);


		if (!$subscriptionRequest->getPlan() || !$subscriptionId) {
			return $response->withStatus(400);
		}

		$sub = $subscriptionRequest->getSubscription();
		$res = $sub->updateSubscription(
			$subscriptionId,
			$subscriptionRequest->getVenues(),
			$subscriptionRequest->getContacts(),
			$orgId
		);

		if ($res['status'] === 200) {
			$hook =  $this->webhook($res['message']);
			return $response->withJson($hook, $hook['status']);
		}

		return $response->withJson($res, $res['status']);
	}

	public function postTrialSubscription(Request $request, Response $response)
	{
		$body = $request->getParsedBody();
		$user = $this->userProvider->getOauthUser($request);

		$organization = $this->getOrganizationForBody($body, $user);
		if ($organization->getChargebeeCustomerId() === null) {
			$chargebeeCustomer = new Billing\ChargebeeCustomer($user);
			$subscriptionRequest = new SubscriptionsRequest($request);
			$subscriptionRequest->getSubscription()->createChargeBeeCustomer($chargebeeCustomer);
			$organization->setChargebeeCustomerId($chargebeeCustomer->getId());
			if (!is_null($body['referralOrgId'])) {
				$referralOrg = $this->organizationService->getOrganisationById($body['referralOrgId']);
				if (!is_null($referralOrg)) {
					if ($referralOrg->getType() === 'reseller') {
						$organization->setParent($referralOrg);
					}
				}
			}
		}
		$this->em->persist($user);
		$this->em->persist($organization);
		$this->em->flush();

		$request = $request->withAttribute('orgId', $organization->getId()->toString());

		return $this->postSubscription($request, $response);
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


	public function getSmsLedger(Request $request, Response $response)
	{

		return $response->withJson(
			$this->smsTransactions->getSmsLedgerTransactions(
				new FromQuery($request)
			)
		);
	}

	public function deductSmsLedger(Request $request, Response $response)
	{
		$organisationSubscription = $this->getOrganisationSubscription($request->getAttribute('orgId'));
		$number = $request->getParsedBodyParam('phone', null);
		if (is_null($number)) {
			return $response->withJson('REQUEST_INVALID_PHONE_MISSING', 400);
		}
		return $response->withJson(
			$this->smsTransactions->deductCredits(
				$organisationSubscription->getOrganisation(),
				1,
				$request->getParsedBodyParam('phone', null)

			)
		);
	}

	public function addCreditHostedPageRoute(Request $request, Response $response): Response
	{
		$credits = (int)$request->getParsedBodyParam('credits', 0);

		$organisationSubscription = $this->getOrganisationSubscription($request->getAttribute('orgId'));
		$sub = new Billing\Subscription(
			$organisationSubscription->getPlan(),
			$organisationSubscription->getAnnual(),
			$organisationSubscription->getCurrency(),
			$organisationSubscription->getAddons()
		);

		return  $response->withJson(
			$sub->createSmsCreditInvoice(
				$credits,
				$organisationSubscription
			)
		);
	}

	public function postSubscription(Request $request, Response $response)
	{

		$orgId = $request->getAttribute('orgId');
		$organisationSubscription = $this->getOrganisationSubscription($orgId);

		if ($organisationSubscription) {
			$request = $request->withAttribute('subscriptionId', $organisationSubscription->getSubscriptionId());
			return $this->putSubscription($request, $response);
		}

		$subscriptionRequest = new SubscriptionsRequest($request);

		if (!$subscriptionRequest->getPlan()) {
			return $response->withStatus(400);
		}

		$organisation = $this->organizationService->getOrganisationById($orgId);
		$customerId = $organisation->getChargebeeCustomerId();
		$sub = new Billing\Subscription(
			$subscriptionRequest->getPlan(),
			$subscriptionRequest->getAnnual(),
			$subscriptionRequest->getCurrency(),
			$subscriptionRequest->getAddons(),
			$subscriptionRequest->getTrial()
		);
		$res = $sub->createSubscription(
			$customerId,
			$subscriptionRequest->getVenues(),
			$subscriptionRequest->getContacts(),
			$orgId
		);

		if ($res['status'] === 200) {
			$hook = $this->webhook($res['message']);
			return $response->withJson($hook, $hook['status']);
		}

		return $response->withJson($res, $res['status']);
	}
	public function updateOrCreateOrganisationSubscription(
		string $organizationId,
		string $subscriptionId,
		array $addons,
		int $contacts,
		int $venues,
		string $plan,
		string $currency,
		string $status,
		bool $annual
	): Subscriptions {



		/** @var Organization $organization */
		$organization = $this->organizationService->getOrganisationById($organizationId);
		$organisationSubscription = $this
			->em->getRepository(Subscriptions::class)->findOneBy([
				'organizationId' => $organizationId
			]);

		if (empty($organisationSubscription)) {
			$organisationSubscription = new Subscriptions(
				$organization,
				$subscriptionId,
				$addons,
				$contacts,
				$venues,
				$plan,
				$currency,
				$status,
				$annual
			);
		} else {
			$organisationSubscription->setPlan($plan);
			$organisationSubscription->setContacts($contacts);
			$organisationSubscription->setVenues($venues);
			$organisationSubscription->setCurrency($currency);
			$organisationSubscription->setAddons($addons);
			$organisationSubscription->setStatus($status);
			$organisationSubscription->setAnnual($annual);
		}
		$this->em->persist($organisationSubscription);
		$this->em->flush();



		return $organisationSubscription;
	}

	public function webhook(array $subscription, $eventType = '')
	{
		$organizationId = null;
		$subscriptionId = $subscription['id'];
		$currency = $subscription['currency_code'];
		$plan = $this->getPlanIdFromChargebeeId($subscription['plan_id']);
		$contacts = 0;
		$venues = 0;
		$status = $subscription['status'];
		$addons  = [];
		$annual = $subscription['billing_period_unit'] === 'year';

		if (array_key_exists('cf_organisation_id', $subscription)) {
			$organizationId = $subscription['cf_organisation_id'];
		}

		if (is_null($organizationId)) {
			return Http::status(403, 'MISSING_ORG_ID');
		}



		if (array_key_exists('addons', $subscription)) {
			foreach ($subscription['addons'] as $addon) {
				$realId = $this->getPlanIdFromChargebeeId($addon['id']);
				if ($realId === 'contacts') {
					$contacts = $addon['quantity'];
					continue;
				}
				if ($realId === 'venues') {
					$venues = $addon['quantity'];
					continue;
				}
				$addons[] = $realId;
			}
		}

		$subscription = $this->updateOrCreateOrganisationSubscription(
			$organizationId,
			$subscriptionId,
			$addons,
			$contacts,
			$venues,
			$plan,
			$currency,
			$status,
			$annual
		);

		if ($eventType === 'subscription_renewed' && !$subscription->isLegacy() && $subscription->getPlan() !== $subscription::PLAN_FREE) {
			$this->smsTransactions->addCredits($subscription->getOrganisation(), $subscription->getIncludedSmsCredits());
		}

		return Http::status(200, $subscription->jsonSerialize());
	}



	public function getPlanIdFromChargebeeId(string $chargebeeId)
	{
		$arr = explode("-", $chargebeeId, 2);
		return $arr[0];
	}

	public function hasValidSubscriptionRoute(Request $request, Response $response)
	{
		return $response->withJson($this->hasValidSubscription($request->getAttribute('orgId')), 200);
	}

	public function hasValidSubscription(string $orgId): bool
	{
		$subscription = $this
			->em->getRepository(Subscriptions::class)->findOneBy([
				'organizationId' => $orgId
			]);
		/**
		 * Manage legacy case where organisation subscription may not exist
		 */
		if (is_null($subscription)) {
			return true;
		}
		if (
			$subscription->getStatus() === 'active' ||
			$subscription->getStatus() === 'in_trial'
			|| $subscription->getStatus() === 'future'
		) {
			return true;
		}

		return false;
	}
}
