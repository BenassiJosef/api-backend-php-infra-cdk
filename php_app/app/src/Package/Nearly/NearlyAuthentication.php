<?php


namespace App\Package\Nearly;

use App\Controllers\Clients\_ClientAgentController;
use App\Controllers\Clients\_ClientsController;
use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Integrations\OpenMesh\OpenMeshNearlySettings;
use App\Controllers\Integrations\Radius\RadiusNearlySettings;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Integrations\UniFi\_UniFiController;
use App\Controllers\Nearly\_NearlyAuthenticationController;
use App\Models\DataSources\DataSource;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\User\UserProfileMacAddress;
use App\Models\UserProfile;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\StatementExecutor;
use App\Package\Vendors\Information;
use App\Utils\Http;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

/**
 * Class NearlyAuthenticationException
 * @package App\Package\Nearly
 */
class NearlyAuthenticationException extends Exception
{
}

class NearlyAuthentication
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var NearlyImpression $impression
	 */
	private $impression;

	/**
	 * @var RadiusNearlySettings $radius
	 */
	private $radius;

	/**
	 * @var _HooksController $hooks
	 */
	private $hooks;


	/**
	 * @var ProfileInteractionFactory $interaction
	 */
	private $interaction;

	/**
	 * @var Information $vendors
	 */
	private $vendors;

	/**
	 * NearlyAuthentication constructor.
	 * @param EntityManager $entityManager
	 * @param ProfileInteractionFactory $profileInteractionFactory
	 */
	public function __construct(
		EntityManager $entityManager,
		ProfileInteractionFactory $profileInteractionFactory
	) {
		$this->entityManager = $entityManager;
		$this->impression    = new NearlyImpression($this->entityManager);
		$this->radius        = new RadiusNearlySettings($this->entityManager);
		$this->vendors       = new Information($this->entityManager);
		$this->hooks         = new _HooksController($this->entityManager);
		$this->interaction   = $profileInteractionFactory;
	}

	public function postAuthentication(Request $request, Response $response)
	{



		if (is_null($request->getParsedBodyParam('settings'))) {
			return $response->withJson('MISSING_SETTINGS', 400);
		}
		$input  = new NearlyInput();
		$params = $input::createFromArray($request->getParsedBodyParam('settings'));
		$params->setImpressionId($request->getParsedBodyParam('impression_id', null));
		$params->setProfileId((int)$request->getParsedBodyParam('profile_id'));
		$opt = $request->getParsedBodyParam('opt_in');
		$params->setMarketingOptIn($opt['marketing']);
		$params->setDataOptIn($opt['data']);
		$session = $this->createSession($params);
		if (is_null($session)) {
			return $response->withJson([
				'message' => 'SESSION_INVALID',
				'body' => $request->getParams(),
				'params' => $params->jsonSerialize()
			], 400);
		}
		if (!is_null($params->getImpressionId())) {
			$this->impression->convertImpression($params->getImpressionId(), $params->getProfileId());
		}

		return $response->withJson($session->jsonSerialize());
	}

	/**
	 * @param NearlyInput $input
	 * @return NearlyAuthenticationResponse
	 * @throws InteractionAlreadyEndedException
	 */

	public function createSession(NearlyInput $input): ?NearlyAuthenticationResponse
	{
		/**
		 * @var LocationSettings $location
		 */
		$location = $this
			->entityManager
			->getRepository(LocationSettings::class)
			->findOneBy(
				[
					'serial' => $input->getSerial()
				]
			);

		$nearlyProfile = new NearlyProfile($this->entityManager);
		/**
		 * @var UserProfile $profile
		 */

		$profile       = $nearlyProfile->getProfileFromMac($input);
		if (is_null($profile)) {
			if (is_null($input->getProfileId())) {
				throw new NearlyAuthenticationException('Profile id not set');
			}
			$profile           = $this
				->entityManager->getRepository(UserProfile::class)->find($input->getProfileId());
			if (is_null($profile)) {
				throw new NearlyAuthenticationException('Profile not found');
			}
			$macAddressProfile = new UserProfileMacAddress(
				$profile,
				$input->getDataOptIn() ? $input->getMac() : $input->getShaMac()
			);
			$this->entityManager->persist($macAddressProfile);
		}

		$type = $location->getNiceType();

		$this->createWiFiInteraction(
			$profile,
			$location->getOrganization(),
			$input->getSerial(),
			$input->getDataOptIn(),
			$input->getMarketingOptIn()
		);

		$inform = $this->vendors->getFromSerial($input->getSerial());
		$vendor = $inform->getVendorSource();

		if ($vendor->getRadius()) {
			$radius = new _NearlyAuthenticationController();
			$radius->createOrFind($input->getSerial(), $input->getProfileId());
		}

		if ($vendor->getAuthMethod() === 'challenge') {
			if (is_null($input->getPort())) {
				throw new NearlyAuthenticationException('Port not found');
			}

			if (is_null($input->getChallenge())) {
				throw new NearlyAuthenticationException('Challenge not found');
			}
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];

		$dd = new DeviceDetector($ua);
		$dd->discardBotInformation();
		$dd->skipBotDetection();
		$dd->parse();

		$ca = new _ClientAgentController($this->entityManager);
		$ca->create(
			[
				'browser' => $dd->getClient(),
				'os'      => $dd->getOs(),
				'device'  => [
					'mobile'     => $dd->getDevice(),
					'type'       => $dd->getDeviceName(),
					'brand'      => $dd->getBrandName(),
					'short_name' => $dd->getBrand(),
					'model'      => $dd->getModel(),
					'mac'        => $input->getMac()
				],
			]
		);

		$session = new _ClientsController($this->entityManager);

		$user = $session->create(
			$input,
			$type
		);

		if ($session->checkEmail($profile->getEmail())) {
			$client = new QueueSender();
			$client->sendMessage(
				[
					'notificationContent' => [
						'objectId'  => $input->getProfileId(),
						'title'     => 'Captured Connection',
						'kind'      => 'capture_connected',
						'link'      => '/analytics/connections',
						'serial'    => $input->getSerial(),
						'profileId' => $input->getProfileId(),
						'message'   => $profile->getEmail() . ' just visited your venue'
					]
				],
				QueueUrls::NOTIFICATION
			);
		}

		$this->hooks->serialToHook(
			$input->getSerial(),
			'connection',
			array_merge($user->jsonZapierSerialize(), ['profile' => $profile->zapierSerialize($input->getSerial())])
		);

		$auth = new NearlyAuthenticationResponse($input, $vendor);

		if ($vendor->getKey() === 'mikrotik' || $vendor->getKey() === 'ignitenet') {
			$auth->getMikrotikRedirect($type);
			return $auth;
		}

		if ($vendor->getAuthMethod() === 'wispr' || $vendor->getAuthMethod() === 'link') {
			return $auth;
		}

		if ($vendor->getAuthMethod() === 'challenge') {
			$auth->setRedirectUri(
				$this->radius->radiusLink($input)
			);
			return $auth;
		}

		if ($vendor->getAuthMethod() === 'unifi') {
			$this->unifiAuth($input);
			$auth->setOnline(false);
			return $auth;
		}

		return $auth;
	}


	public function unifiAuth(NearlyInput $input)
	{
		$newUnifi = new _UniFiController($this->entityManager);
		$newUnifi->auth($input);
	}

	public function createWiFiInteraction(
		UserProfile $profile,
		Organization $organization,
		string $serial,
		bool $dataOptIn,
		bool $marketingOptIn
	) {
		$dataSource = $this->interaction->getDataSource('wifi');
		/**
		 * @var OrganizationRegistration $organizationRegistration
		 */
		$organizationRegistration = $this
			->entityManager
			->getRepository(OrganizationRegistration::class)
			->findOneBy(
				[
					'profileId'      => $profile->getId(),
					'organizationId' => $organization->getId()
				]
			);

		if (is_null($organizationRegistration)) {
			$organizationRegistration = new OrganizationRegistration($organization, $profile);
		}

		$organizationRegistration->setDataOptIn($dataOptIn);
		$organizationRegistration->setSmsOptIn($marketingOptIn);
		$organizationRegistration->setEmailOptIn($marketingOptIn);
		$this->entityManager->persist($organizationRegistration);
		$this->entityManager->flush();

		$profileInteraction = $this->interaction
			->makeEmailingProfileInteraction(
				new InteractionRequest(
					$organization,
					$dataSource,
					[
						$serial
					],
					1
				)
			);
		$profileInteraction->saveUserProfile($profile);
	}
}
