<?php

namespace App\Controllers\Clients;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Locations\Reports\_RegistrationReportController;
use App\Controllers\Nearly\_NearlyAuthenticationController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Models;
use App\Models\UserProfile;
use App\Package\Nearly\NearlyInput;
use App\Utils\Http;
use App\Utils\MacFormatter;
use App\Utils\PushNotifications;
use App\Utils\Validation;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class _ClientsController
 * @package App\Controllers\Clients
 */
class _ClientsController
{

	protected $em;
	protected $pushNotifications;
	protected $hooksController;

	/**
	 * _ClientsController constructor.
	 * @param EntityManager $em
	 * @param PushNotifications $pushNotifications
	 */

	public function __construct(EntityManager $em, PushNotifications $pushNotifications = null, _HooksController $hooksController = null)
	{
		$this->em = $em;
		if (is_null($pushNotifications)) {
			$pushNotifications = new PushNotifications($this->em);
		}
		$this->pushNotifications = $pushNotifications;
		if (is_null($hooksController)) {
			$hooksController = new _HooksController($this->em);
		}
		$this->hooksController = $hooksController;
	}

	/*public function createSession(array $body)
    {
        $ca  = new _ClientAgentController($this->em);
        $res = $ca->create($body);

        return $response->withJson($res);
    }*/

	public function createRoute(Request $request, Response $response)
	{

		$body = $request->getParsedBody();
		$send = $this->createSession($body);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	/**
	 * @param $email
	 * @return bool
	 */
	public function checkEmail($email)
	{
		$find1 = strpos($email, '@');
		$find2 = strpos($email, '.');
		return ($find1 !== false && $find2 !== false && $find2 > $find1);
	}

	public function createSession(array $body)
	{
		$validationArgs = [
			'profileId',
			'mac',
			'ip',
			'serial',
			'auth_time',
			'type'
		];

		if (isset($body['shadowedMac'])) {
			$body['mac'] = $body['shadowedMac'];
		}

		$valid = Validation::pastRouteBodyCheck($body, $validationArgs);

		$email = null;
		if (isset($body['email'])) {
			$email = $body['email'];
		}

		if (isset($body['agent'])) {
		}

		if ($valid === true) {
			$user = $this->create(
				$body['profileId'],
				$body['mac'],
				$body['ip'],
				$body['serial'],
				$body['auth_time'],
				$body['type'],
				$email
			);

			if ($this->checkEmail($email)) {
				$client = new QueueSender();
				$client->sendMessage([
					'notificationContent' => [
						'objectId' => $body['profileId'],
						'title' => 'Captured Connection',
						'kind' => 'capture_connected',
						'link' => '/analytics/connections',
						'serial' => $body['serial'],
						'profileId' => $body['profileId'],
						'message' => $email . ' just visited your venue'
					]
				], QueueUrls::NOTIFICATION);
			}

			$this->hooksController->serialToHook($body['serial'], 'connection', $user);

			if ($body['radius'] === true) {
				$radius = new _NearlyAuthenticationController();
				$radius->createOrFind($body['serial'], $body['profileId']);
			}


			return Http::status(200, $user);
		}

		return Http::status(400, [
			'reason' => 'MISSING_ITEMS',
			'items_missing' => $valid
		]);
	}


	/**
	 * @param string $serial
	 * @param int $profileId
	 * @param int $initialVisits
	 * @throws DBALException
	 */
	public function trackRegistration(string $serial, int $profileId, int $initialVisits = 1)
	{
		$query = 'INSERT INTO `core`.`user_registrations` 
                        (serial, profile_id, number_of_visits, created_at, last_seen_at, email_opt_in_date, sms_opt_in_date, location_opt_in_date)
                        VALUES (:serial, :profileId, :initialVisits, NOW(), NOW(), NOW(), NOW(), NOW()) 
                        ON DUPLICATE KEY UPDATE 
                            `number_of_visits` = `number_of_visits` + 1, 
                            `last_seen_at` = NOW();';
		$statement = $this
			->em
			->getConnection()
			->prepare($query);

		$statement->bindValue(':serial', $serial, ParameterType::STRING);
		$statement->bindValue(':profileId', $profileId, ParameterType::INTEGER);
		$statement->bindValue(':initialVisits', $initialVisits);

		$statement->execute();
		if ($statement->rowCount() === 1) {
			/**
			 * @var UserProfile  $profile
			 */
			$profile = $this->em->getRepository(UserProfile::class)->find($profileId);

			if (is_null($profile)) {
				return;
			}

			if ($this->checkEmail($profile->getEmail())) {
				$client = new QueueSender();
				$client->sendMessage([
					'notificationContent' => [
						'objectId' => $profile->getId(),
						'title' => 'User registration',
						'kind' => 'capture_registered',
						'link' => '/analytics/registrations',
						'profileId' => $profile->getId(),
						'serial' => $serial,
						'message' => $profile->getEmail() . ' has just registered'
					]
				], QueueUrls::NOTIFICATION);
			}
			//$this->hooksController->serialToHook($serial, 'registration_unvalidated', $profile->zapierSerialize($serial));
		}
	}

	/**
	 * @param NearlyInput $input
	 * @param string $type
	 * @return Models\UserData
	 */
	public function create(
		NearlyInput $input,
		string $type
	): Models\UserData {

		$user = new Models\UserData();


		$user->profileId = $input->getProfileId();
		$user->mac = $input->getMac();
		$user->ip = $input->getIp();
		$user->serial = $input->getSerial();
		$user->authTime = $input->getAuthTime();
		$user->timestamp = new DateTime();

		$user->auth = 1;
		$user->type = $type;
		$user->dataDown = 0;
		$user->dataUp = 0;

		$this->em->persist($user);
		$this->em->flush();

		$this->trackRegistration($input->getSerial(), $input->getProfileId());
		return $user;
	}

	public function update(int $download = 0, int $upload = 0, string $mac = '', string $serial = '', string $ip = '')
	{
		$newClientsUpdateController = new _ClientsUpdateController($this->em);
		$mac = str_replace('-', ':', $mac);

		return $newClientsUpdateController->update($download, $upload, $mac, $serial, $ip);
	}
}
