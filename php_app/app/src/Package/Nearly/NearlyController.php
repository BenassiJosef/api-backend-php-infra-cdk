<?php

namespace App\Package\Nearly;

use App\Controllers\Billing\Subscription;
use App\Controllers\Integrations\DDNS\DDNS;
use App\Models\Locations\LocationSettings;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\OrganizationSettingsService;
use App\Package\Reviews\ReviewService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

class NearlyController
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var NearlyBranding $branding
	 */
	protected $branding;

	/**
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * @var NearlyPayment $payment
	 */
	protected $payment;

	/**
	 * @var NearlyStory $story
	 */
	protected $story;

	/**
	 * @var NearlyAuthentication $authentication
	 */
	protected $authentication;

	/**
	 * @var NearlyImpression $impression
	 */
	protected $impression;

	/**
	 * @var Subscription $subscription
	 */
	protected $subscription;


	/**
	 * @var ReviewService $reviewService
	 */
	protected $reviewService;

	/**
	 * NearlyController constructor.
	 * @param EntityManager $entityManager
	 * @param Logger $logger
	 * @param NearlyAuthentication $nearlyAuthentication
	 */
	public function __construct(
		EntityManager $entityManager,
		Logger $logger,
		NearlyAuthentication $nearlyAuthentication,
		ReviewService $reviewService
	) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->branding = new NearlyBranding();
		$this->payment = new NearlyPayment($this->entityManager, $this->logger);
		$this->impression = new NearlyImpression($this->entityManager);
		$this->authentication = $nearlyAuthentication;
		$this->story = new NearlyStory($this->entityManager);
		$this->subscription = new Subscription(new OrganizationService($this->entityManager), $this->entityManager);
		$this->reviewService = $reviewService;
	}

	public function getSettings(Request $request, Response $response)
	{

		$nearlyInput = new NearlyInput();
		$params = $nearlyInput->createFromArray($request->getQueryParams());
		$params->setRemoteIp($request);

		if (!$params->getMac() || !$params->getSerial()) {
			return $response->withStatus(400);
		}

		$results = $this->getAllSettings($params);
		if (!is_null($results)) {
			return $response->withJson($results);
		}
		return $response->withJson($results, 404);
	}

	public function getAllSettings(
		NearlyInput $input
	): ?NearlyOutput {
		$serial = $input->getSerial();
		/**
		 * @var LocationSettings $location
		 */
		$location = $this->entityManager->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);

		if (is_null($location) || is_null($location->getOrganizationId())) {
			return null;
		}

		if (function_exists("newrelic_add_custom_parameter")) {
			newrelic_add_custom_parameter("event_serial", $location->getSerial());
		}
		$nearlyProfile = new NearlyProfile($this->entityManager);
		$profile = $nearlyProfile->getProfileFromMac($input);
		$output = new NearlyOutput($location, $profile);
		$registration = $nearlyProfile->getOptIn($location->getOrganization(), $profile);
		$output->setOrganisationRegistration($registration);
		$validSubscription = $this->subscription->hasValidSubscription($location->getOrganizationId()->toString());
		$output->setValidSubscription($validSubscription);

		$organisationSettings = new OrganizationSettingsService($this->entityManager);
		$output->setTrackAndTraceEnabled(
			$organisationSettings
				->settings(
					$location->getOrganizationId()
				)
				->getSettings()
				->canSendCheckoutEmail()
		);

		if ($validSubscription === false) {
			return $output;
		}
		if (!is_null($profile) && $nearlyProfile->isBlocked($serial, $input->getMac())) {
			$output->setBlocked(true);
			return $output;
		}

		if (!$input->getPreview()) {
			if ($location->getOtherSettings()->getDdnsEnabled() && !is_null($input->getRemoteIp())) {
				$ddns = new DDNS($input->getRemoteIp(), $serial);
				$ddns->save();
			}
		}

		$this->branding->parseNearlyBranding($location->getBrandingSettings(), $location->getSerial());

		/**
		 *Location types
		 * 0 = FREE
		 * 1 = PAID
		 * 2 = HYBRID
		 */
		if ($location->getType() !== 0) {
			if ($location->getType() === 2 && is_null($profile)) {
				$location->setType(0);
			} elseif ($location->getType() === 2) {
				$checkLimit = $this->payment->checkHybridDataUsage(
					$profile->getId(),
					$serial,
					$location->getOtherSettings()->getHybridLimit(),
				);
				$location->setType(0);
				if ($checkLimit) {
					$location->setType(1);
				}
			}
			$this->payment->load($output);
		}

		$input->setDataOptIn($location->getNiceType() === 'paid' ? true : $registration->getDataOptIn());
		$input->setMarketingOptIn(
			$location->getNiceType() === 'paid' ? true : $registration->getEmailOptIn() && $registration->getSmsOptIn()
		);
		$this->entityManager->clear(LocationSettings::class);
		if ($location->getNiceType() === 'free' && $output->getProfile() && $validSubscription) {
			$reviewSettings = $this->reviewService->getReviewFromSerial($location->getSerial());
			if (!is_null($reviewSettings)) {
				$canReview = $this->reviewService->canReview($reviewSettings, $output->getProfile()->getId(), false);
				if ($canReview && $reviewSettings->getHappyOrNot()) {
					$output->setReviewSettings($reviewSettings);
				}
			}
		}
		/*
        if ($send['location']['type'] === 'free') {
        $sessionAuth             = $devicesController->checkAuth($serial, $mac);
        $send['session']['auth'] = $sessionAuth['message']['auth'];
        }
         */
		/*
        if (($send['location']['type'] === 'paid' || $send['location']['type'] === 'hybrid')) {

        if ($send['paymentSettings']['stripe'] === true) {

        if ($stripeCustomer['status'] === 200) {

        $newCardsController = new _StripeCardsController($this->logger, $this->entityManager);
        $cardsRequest       = $newCardsController->stripeListCards(
        $stripeCustomer['message']['stripe_user_id'],
        $stripeCustomer['message']['stripeCustomerId']
        );

        if ($cardsRequest['status'] === 200) {
        if (!is_array($cardsRequest['message']->data)) {
        $send['profile']['cards'] = [];
        } else {
        foreach ($cardsRequest['message']->data as $card) {
        $card['default'] = false;
        if ($cardsRequest['message']->default_source === $card->id) {
        $card['default'] = true;
        }
        $send['profile']['cards'][] = $card;
        }
        }
        }
        }
        }
        }
         */

		if (!$input->getPreview()) {
			$this->impression->load($output);
		}

		$this->story->load($output);

		if ($output->shouldAutoAuth()) {
			$input->setProfileId($output->getProfile()->getId());
			$auth = $this->authentication->createSession($input);
			if (is_null($auth)) {
				return null;
			}

			$output->setAuthPayload($auth);
		}

		$this->entityManager->flush();

		return $output;
	}
}
