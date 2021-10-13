<?php

namespace App\Controllers\Registrations;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models;
use App\Models\NetworkAccess;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Package\Profile\ProfileMerger;
use App\Utils\Http;
use DateTime;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

class _RegistrationsController
{

	protected $em;
	protected $mail;

	private $userProfileKeys = [
		'id',
		'email',
		'first',
		'last',
		'phone',
		'phonecc',
		'postcode',
		'postcodeValid',
		'phoneValid',
		'opt',
		'age',
		'birthMonth',
		'birthDay',
		'ageRange',
		'gender',
		'verified',
		'verified_id',
		'timestamp',
		'lat',
		'lng',
		'updated',
		'country',
		'countryCode',
		'custom',
	];

	/**
	 * @var ProfileMerger $profileMerger
	 */
	private $profileMerger;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->profileMerger = new ProfileMerger($em);
	}

	public function updateRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody() ?? [];
		$serial = $request->getAttribute('serial');

		$profile = $this->updateOrCreate($body, $serial);

		if ($profile->getVerified() === false && !is_null($profile->getEmail())) {
			if (!empty($profile->getEmail())) {
				$publisher = new QueueSender();
				$publisher->sendMessage(
					[
						'serial' => $serial,
						'id' => $profile->getId(),
					],
					QueueUrls::EMAIL_VALIDATION
				);
			}
		}

		$this->em->clear();

		return $response->withJson($profile->jsonSerialize());
	}

	public function updateNearlyUserRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody();

		$send = $this->updateNearlyUser($body);

		return $response->withJson($send, $send['status']);
	}

	/**
	 * @param array $body
	 * @return array
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws ConnectionException
	 * @throws Throwable
	 */
	public function updateNearlyUser(array $body)
	{
		/** @var UserProfile | null $user */
		$user = $this
			->em
			->getRepository(UserProfile::class)
			->findOneBy(
				[
					'id' => $body['id'],
				]
			);

		if (is_null($user)) {
			return Http::status(409, 'INVALID_USER');
		}

		/** @var UserProfile | null $mergeProfile */
		$mergeProfile = null;
		if (array_key_exists('email', $body)) {
			$email = $body['email'];
			$mergeProfile = $this
				->em
				->getRepository(UserProfile::class)
				->findOneBy(['email' => $email]);
		}

		if ($mergeProfile !== null) {
			if ($user->getId() !== $mergeProfile->getId()) {
				$this->profileMerger->merge($mergeProfile, $user);
			}
		}

		foreach ($body as $key => $item) {
			if (in_array($key, $this->userProfileKeys)) {
				if (!is_null($item)) {
					$user->$key = $item;
				}
			}
		}

		$user->updated = new DateTime();
		$user->verified_id = md5($body['email']);

		$this->em->persist($user);
		$this->em->flush();

		return Http::status(200, $user->getArrayCopy());
	}

	public function get(Request $request, Response $response)
	{

		$user = $request->getAttribute('user');
		$role = $user['role'];
		$uid = $user['uid'];

		$id = $request->getAttribute('route')->getArgument('id');

		if ($role === 1) {
			$where = 'reseller';
		} elseif ($role === 2) {
			$where = 'admin';
		}

		$results = $this->em->createQueryBuilder()
			->select('r')
			->from(UserProfile::class, 'r')
			->leftJoin(UserData::class, 'd', 'WITH', 'd.profileId = r.id')
			->leftJoin(NetworkAccess::class, 'a', 'WITH', 'd.serial = a.serial AND a.' . $where . ' = :uid')
			->where('r.id = :id')
			->setParameter('id', $id)
			->setParameter('uid', $uid);

		$results = $results
			->getQuery()
			->getArrayResult();

		$this->em->clear();

		return $response->withJson($results);
	}

	public function getAll(Request $request, Response $response)
	{
		$user = $request->getAttribute('user');
		$role = $user['role'];
		$uid = $user['uid'];

		if ($role === 1) {
			$where = 'reseller';
		} elseif ($role === 2) {
			$where = 'admin';
		}

		$results = $this->em->createQueryBuilder()
			->select('r')
			->from(UserProfile::class, 'r')
			->leftJoin(UserData::class, 'd', 'WITH', 'd.profileId = r.id')
			->leftJoin(NetworkAccess::class, 'a', 'WITH', 'd.serial = a.serial AND a.' . $where . ' = :uid')
			->setParameter('uid', $uid)
			->setMaxResults(200);

		$results = $results->getQuery()
			->getArrayResult();

		$this->em->clear();

		return $response->withJson($results);
	}

	private function parseCustomQuestions(string $serial, array $customer = []): array
	{
		$customQuestions = [
			$serial => [],
		];

		foreach ($customer as $key => $value) {
			if (in_array($key, $this->userProfileKeys)) {
				continue;
			}
			if (strlen($key) < 30) {
				continue;
			}
			$customQuestions[$serial][$key] = $value;
		}
		return $customQuestions;
	}

	/**
	 * @param string $serial
	 * @param array $customer
	 * @return UserProfile
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	private function createUserProfile(string $serial, array $customer = []): UserProfile
	{
		$customQuestions = $this->parseCustomQuestions($serial, $customer);
		$profile = new UserProfile();

		if (array_key_exists('email', $customer)) {
			$profile->verified_id = md5($customer['email']);
		}
		foreach ($customer as $key => $item) {
			$profile->$key = $item;
		}
		if (!empty($customQuestions[$serial])) {
			$profile->custom = $customQuestions;
		}

		$this->em->persist($profile);
		$this->em->flush();
		return $profile;
	}

	private function getProfileByEmail(string $email): ?UserProfile
	{
		/** @var UserProfile | null $profile */
		$profile = $this
			->em
			->getRepository(UserProfile::class)
			->findOneBy(
				[
					'email' => $email,
				]
			);
		return $profile;
	}

	private function getProfileById($id): ?UserProfile
	{
		/** @var UserProfile | null $profile */
		$profile = $this
			->em
			->getRepository(UserProfile::class)
			->findOneBy(
				[
					'id' => $id,
				]
			);
		return $profile;
	}

	public function getProfile(string $id = '', string $email = '')
	{
		$fetchProfile = $this->em->createQueryBuilder()
			->select('u')
			->from(UserProfile::class, 'u');

		if (!empty($id)) {
			$fetchProfile = $fetchProfile->where('u.id = :id')
				->setParameter('id', $id);
		} elseif (!empty($email)) {
			$fetchProfile = $fetchProfile->where('u.email = :email')
				->setParameter('email', $email);
		}
		$fetchProfile = $fetchProfile->getQuery()
			->getArrayResult();

		if (!empty($fetchProfile)) {
			return $fetchProfile[0];
		}

		return false;
	}

	/**
	 * @param array $customer
	 * @param string $serial
	 * @return array|bool
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws Exception
	 */
	public function createUser($customer, $serial)
	{
		$profile = $this->createUserProfile($serial, $customer);
		if (!empty($profile->getArrayCopy())) {
			return $profile->getArrayCopy();
		}
		return false;
	}

	/**
	 * @param array $customer
	 * @param $serial
	 * @return UserProfile
	 * @throws DBALException
	 * @throws ORMException
	 * @throws Throwable
	 */
	public function updateOrCreate($customer = [], string $serial = ''): UserProfile
	{
		if (array_key_exists('id', $customer) && empty($customer['id'])) {
			unset($customer['id']);
		}
		$customerId = $customer['id'] ?? null;
		$customerEmail = $customer['email'] ?? null;

		$idProfile = null;
		if (!empty($customerId)) {
			$idProfile = $this->getProfileById($customerId);
		}

		$emailProfile = null;
		if (!empty($customerEmail)) {
			$emailProfile = $this->getProfileByEmail($customerEmail);
		}

		$profile = $idProfile;
		if ($idProfile === null && $emailProfile === null) {
			$profile = $this->createUserProfile($serial, $customer);
		}
		if ($idProfile === null && $emailProfile !== null) {
			$profile = $emailProfile;
		}
		if (($idProfile !== null && $emailProfile !== null) && ($idProfile->getId() !== $emailProfile->getId())) {
			$profile = $this->profileMerger->merge($idProfile, $emailProfile);
		}
		$profile = $this->updateUserProfile($serial, $customer, $profile);

		return $profile;
	}

	private function updateUserProfile(
		string $serial,
		array $customer,
		UserProfile $userProfile
	): UserProfile {
		$oldCustomQuestions = $userProfile->getCustom();
		$newCustomQuestions = $this->parseCustomQuestions($serial, $customer);
		$customQuestions = array_merge($oldCustomQuestions, $newCustomQuestions);
		$userProfile
			->setCustom($customQuestions)
			->setUpdated(new DateTime());
		foreach ($customer as $key => $item) {
			if (in_array($key, $this->userProfileKeys)) {
				if (!is_null($item)) {
					$userProfile->$key = $item;
				}
			}
		}

		$this->em->persist($userProfile);
		$this->em->flush();
		return $userProfile;
	}

	/**
	 * @param array $customer
	 * @param string $serial
	 * @return array
	 * @throws DBALException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function updateProfile(array $customer = [], string $serial)
	{
		$custom = $this->handleCustom($customer, $serial);

		if (array_key_exists('email', $customer)) {
			$customer['verified_id'] = md5($customer['email']);
		}

		$customer['updated'] = new DateTime('now');

		if ($custom !== false) {
			$customer['custom'] = $custom;
		}

		/**
		 * @var UserProfile $user
		 */
		$user = $this->em->getRepository(UserProfile::class)->findOneBy(
			[
				'id' => $customer['id'],
			]
		);

		foreach ($customer as $key => $item) {
			if (in_array($key, $this->userProfileKeys)) {
				if (!is_null($item)) {
					$user->$key = $item;
				}
			}
		}

		$this->em->persist($user);
		$this->em->flush();

		return $user->getArrayCopy();
	}

	public function isEmailValid(string $email = '')
	{
		$checkQuery = $this->em->createQueryBuilder()
			->select('u.verified')
			->from(UserProfile::class, 'u')
			->where('u.email = :email')
			->andWhere('u.verified = :num')
			->setParameter('email', $email)
			->setParameter('num', 1)
			->getQuery()
			->getArrayResult();

		if (!empty($checkQuery)) {
			return true;
		}

		return false;
	}

	public function getAccountType($uid = '')
	{
		$get = $this->em->createQueryBuilder()
			->select('u')
			->from(Models\OauthUser::class, 'u')
			->where('u.uid = :id')
			->setParameter('id', $uid)
			->getQuery()
			->getArrayResult();
		if (!empty($get)) {
			if (is_null($get[0]['reseller'])) {
				return $get[0]['admin'];
			}

			return $get[0]['reseller'];
		}

		return false;
	}

	public function handleCustom($customer = [], string $serial)
	{

		$customQuestions = [
			$serial => [],
		];

		foreach ($customer as $key => $value) {
			if (in_array($key, $this->userProfileKeys)) {
				continue;
			}
			$customQuestions[$serial][$key] = $value;
		}

		$getOldCustomData = $this->em->createQueryBuilder()
			->select('u.custom')
			->from(UserProfile::class, 'u')
			->where('u.id = :id')
			->setParameter('id', $customer['id'])
			->getQuery()
			->getArrayResult();

		if (empty($getOldCustomData)) {
			return false;
		}

		if (!is_null($getOldCustomData[0]['custom'])) {
			return array_merge($getOldCustomData[0]['custom'], $customQuestions);
		} else {
			return $customQuestions;
		}
	}
}
