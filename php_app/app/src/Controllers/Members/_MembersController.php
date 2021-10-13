<?php

namespace App\Controllers\Members;

use App\Controllers\Auth\_MagicLink;
use App\Controllers\Auth\_oAuth2Controller;
use App\Controllers\Auth\_oAuth2TokenController;
use App\Controllers\Auth\_PasswordController;
use App\Controllers\Billing\Quotes\_QuotesController;
use App\Controllers\Billing\Quotes\QuoteCreator;
use App\Controllers\Billing\Subscriptions\LocationSubscriptionController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Nearly\EmailValidator;
use App\Models\Locations\LocationSettings;
use App\Models\Members\CustomerPricing;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\Notifications\NotificationType;
use App\Models\Notifications\UserNotificationLists;
use App\Models\OauthUser;
use App\Models\Role;
use App\Models\SubscriptionVatRates;
use App\Package\Billing\ChargebeeCustomer;
use App\Package\Member\MemberService;
use App\Package\Organisations\LocationAccessChangeRequestProvider;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\User;
use App\Package\RequestUser\UserProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig\Token;

class _MembersController
{
	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var _oAuth2Controller
	 */
	protected $auth;

	/**
	 * @var CacheEngine
	 */
	protected $connectCache;
	protected $server;
	protected $mail;

	/**
	 * @var _Mixpanel
	 */
	protected $mp;

	/**
	 * @var _ChargeBeeCustomerController
	 */
	protected $chargebeeCustomerController;

	/**
	 * @var MemberValidationController
	 */
	protected $memberValidationController;

	/**
	 * @var QuoteCreator $quoteCreator
	 */
	protected $quoteCreator;

	/**
	 * @var LocationAccessChangeRequestProvider $locationAccessChangeRequestProvider
	 */
	private $locationAccessChangeRequestProvider;

	/**
	 * @var LocationService $locationService
	 */
	private $locationService;

	/**
	 * @var MemberService
	 */
	private $memberService;

	/**
	 * @var OrganizationService $organizationService
	 */
	private $organizationService;

	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;

	/**
	 * _MembersController constructor.
	 * @param $server
	 * @param EntityManager $em
	 * @param _oAuth2Controller $auth
	 * @param LocationAccessChangeRequestProvider $locationAccessChangeRequestProvider
	 * @param LocationService $locationService
	 * @param MemberService $memberService
	 * @param QuoteCreator|null $quoteCreator
	 * @throws \phpmailerException
	 */
	public function __construct(
		$server,
		EntityManager $em,
		_oAuth2Controller $auth,
		LocationAccessChangeRequestProvider $locationAccessChangeRequestProvider,
		LocationService $locationService,
		MemberService $memberService,
		QuoteCreator $quoteCreator = null
	) {
		$this->em                                  = $em;
		$this->auth                                = $auth;
		$this->server                              = $server;
		$this->connectCache                        = new CacheEngine(getenv('CONNECT_REDIS'));
		$this->mp                                  = new _Mixpanel();
		$this->chargebeeCustomerController         = new _ChargeBeeCustomerController($this->em);
		$this->memberValidationController          = new MemberValidationController($this->em);
		$this->locationAccessChangeRequestProvider = $locationAccessChangeRequestProvider;
		$this->locationService                     = $locationService;
		if ($quoteCreator === null) {
			$quoteCreator = new _QuotesController($em);
		}
		$this->quoteCreator        = $quoteCreator;
		$this->memberService       = $memberService;
		$this->userRoleChecker     = new UserRoleChecker($em);
		$this->organizationService = new OrganizationService($em, $this->userRoleChecker);
	}

	public function createAccountRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody();

		if (empty($body)) {
			$body = $request->getQueryParams();
		}

		$send = $this->createUser($body, $request);

		return $response->withJson($send, $send['status']);
	}

	public function createTrialFromFormRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody();

		if (empty($body)) {
			$body = $request->getQueryParams();
		}

		$send = $this->createTrialFromForm($request, $body);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function createUserRoute(Request $request, Response $response)
	{
		$loggedIn = $request->getAttribute('user');
		$body     = $request->getParsedBody();
		$send     = $this->createUser($body, $request);

		$this->mp->identify($loggedIn['uid'])->track('member_create', $send);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function getMemberRoute(Request $request, Response $response)
	{
		$user = $request->getAttribute('user');
		$send = $this->getMember($user);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function getAllMembersRoute(Request $request, Response $response)
	{
		$queryParams = $request->getQueryParams();

		$role   = isset($queryParams['role']) ? $queryParams['role'] : null;
		$search = isset($queryParams['search']) ? $queryParams['search'] : null;
		$offset = isset($queryParams['offset']) ? $queryParams['offset'] : 0;


		$send = $this->getAllMembers($role, $offset, $request->getAttribute('accessUser'), $search);

		$this->em->clear();

		$this->mp->identify($request->getAttribute('user')['uid'])->track('members_get');

		return $response->withJson($send, $send['status']);
	}

	public function updateRoute(Request $request, Response $response)
	{
		$userToUpdate    = $request->getParsedBody();
		$userDoingUpdate = $request->getAttribute('accessUser');

		if ($userToUpdate['uid'] !== $userDoingUpdate['uid']) {
			return $response->withStatus(403, 'YOU CANNOT UPDATE ANOTHER USER');
		}

		$send = $this->updateUser($userToUpdate);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function updateMemberAccessRoute(Request $request, Response $response)
	{
		$send = $this->updateMemberAccess($request->getAttribute('serial'), $request->getParsedBody());

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function getMemberAccessRoute(Request $request, Response $response)
	{
		$send = $this->getMembersThatHaveAccess($request->getAttribute('serial'));

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function getAllMembers($role, $offset, $user, $search)
	{
		$currentUserRole = $user['role'];

		$getMembers = $this->em->createQueryBuilder()
			->select('u.email, u.role, u.company, u.uid, u.first, u.last')
			->from(OauthUser::class, 'u')
			->where('u.deleted = :bool')
			->setParameter('bool', false);

		if ($currentUserRole === 1 && is_null($role)) {
			$getMembers = $getMembers->andWhere('u.reseller = :reseller')
				->setParameter('reseller', $user['uid']);
		} elseif ($currentUserRole === 1 && !is_null($role)) {
			$getMembers = $getMembers->andWhere('u.reseller = :reseller')
				->andWhere('u.role = :role')
				->setParameter('reseller', $user['uid'])
				->setParameter('role', $role);
		}

		if ($currentUserRole === 2 && is_null($role)) {
			$getMembers = $getMembers->andWhere('u.admin = :admin') // TODO OrgId replace
				->setParameter('admin', $user['uid']);
		} elseif ($currentUserRole === 2 && !is_null($role)) {
			$getMembers = $getMembers->andWhere('u.admin = :admin') // TODO OrgId replace
				->andWhere('u.role = :role')
				->setParameter('admin', $user['uid'])
				->setParameter('role', $role);
		}

		if ($currentUserRole === 0 && !is_null($role)) {
			$getMembers = $getMembers->andWhere('u.role = :role')
				->setParameter('role', $role);
		}

		if (!is_null($search) && !empty($search)) {
			$getMembers = $getMembers->andWhere('u.company LIKE :search OR u.email LIKE :search OR u.uid LIKE :search') // TODO OrgId replace
				->setParameter('search', $search . '%');
		}

		$getMembers = $getMembers->setFirstResult($offset)
			->setMaxResults(25)
			->orderBy('u.uid', 'ASC');

		$results = new Paginator($getMembers);
		$results->setUseOutputWalkers(false);

		$getMembers = $results->getIterator()->getArrayCopy();

		if (empty($getMembers)) {
			return Http::status(204, []);
		}

		$return = [
			'has_more'    => false,
			'total'       => count($results),
			'next_offset' => $offset + 25
		];

		if ($offset <= $return['total'] && count($getMembers) !== $return['total']) {
			$return['has_more'] = true;
		}

		$return['results'] = $getMembers;

		return Http::status(200, $return);
	}

	public function deleteRoute(Request $request, Response $response)
	{

		$loggedIn = $request->getAttribute('user');
		$user     = $request->getAttribute('accessUser');
		$send     = $this->deleteMember($user['uid'], $user);

		$mp = new _Mixpanel();
		$mp->identify($loggedIn['uid'])->track('member_deleted', $send);

		return $response->withJson($send, $send['status']);
	}

	public function deleteMember(string $uid, array $user)
	{
		if (empty($uid)) {
			return Http::status(400, 'UID_NOT_SET');
		}


		$delete = $this->em->createQueryBuilder()
			->update(OauthUser::class, 'o')
			->set('o.deleted', 1)
			->where('o.uid = :uid') // TODO OrgId replace
			->setParameter('uid', $uid)
			->getQuery()
			->execute();

		if ($delete === 1) {
			return Http::status(200, 'MEMBER_DELETED');
		}

		return Http::status(400, 'FAILED_TO_DELETE_MEMBER');
	}

	private static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if (!$length) {
			return true;
		}
		return substr($haystack, -$length) === $needle;
	}

	/**
	 * @param array $userToUpdate
	 * @return int[]
	 * @throws DBALException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function updateUser(array $userToUpdate)
	{
		/** @var OauthUser | null $user */
		$user = $this
			->em
			->getRepository(OauthUser::class)
			->findOneBy(
				[
					'uid' => $userToUpdate['uid']
				]
			);

		if (is_null($user)) {
			return Http::status(404, 'USER_NOT_FOUND');
		}

		if (array_key_exists('email', $userToUpdate)) {
			$email = $userToUpdate['email'];
			$rootOrg            = $this
				->organizationService
				->getRootOrganisation();
			$hasAccessToRootOrg = $this
				->userRoleChecker
				->hasAccessToOrganizationAsRole($user, $rootOrg->getId()->toString(), Role::$allRoles);
			$endsWithStampede = self::endsWith($email, '@stampede.ai');
			if ($hasAccessToRootOrg && !$endsWithStampede) {
				return Http::status(400, 'USER_WITH_ROOT_ACCESS_MUST_BE_STAMPEDE');
			}
		}


		$chargeBeeCustomer = new ChargebeeCustomer($user);
		$this->chargebeeCustomerController->updateCustomer($chargeBeeCustomer->toChargeBeeCustomerForUpdate());

		/**
		 * TODO: Remove when additionalInfo is an option on the front
		 * Update Notification additionalInfo Emails
		 */
		if (array_key_exists('email', $userToUpdate)) {
			$this->em->createQueryBuilder()
				->update(NotificationType::class, 'u')
				->set('u.additionalInfo', ':email')
				->where('u.uid = :user') // TODO OrgId replace
				->andWhere('u.type = :emailType')
				->setParameter('email', $userToUpdate['email'])
				->setParameter('user', $userToUpdate['uid'])
				->setParameter('emailType', 'email')
				->getQuery()
				->execute();
		}

		foreach ($userToUpdate as $key => $value) {
			if (in_array($key, OauthUser::$UPDATABLE_KEYS) && $value !== null) {
				$user->$key = $value;
			}
		}
		$user->edited = new DateTime("now");

		$this->em->persist($user);
		$this->em->flush();

		$this->connectCache->deleteMultiple(
			[
				$user->uid . ':marketing:accessibleLocations',
				$user->uid . ':location:accessibleLocations',
				$user->uid . ':connectNotificationLists',
				$user->uid . ':profile'
			]
		);

		$u = $user->getArrayCopy();

		return Http::status(200, $u);
	}

	public function getMember(array $user)
	{
		return Http::status(200, $user);
	}

	public function updateMemberAccess(string $serial, array $body)
	{
		$getMemberKey = $this->em->getRepository(NetworkAccess::class)->findOneBy(
			[
				'serial' => $serial
			]
		);

		$member = $this->em->getRepository(NetworkAccessMembers::class)->findOneBy(
			[
				'memberId'  => $body['uid'],
				'memberKey' => $getMemberKey->memberKey
			]
		);

		if (is_object($member)) {
			$this->em->remove($member);
			$this->em->flush();

			return Http::status(200, 'USER_ACCESS_REMOVED');
		}

		$newMember = new NetworkAccessMembers($body['uid'], $getMemberKey->memberKey);
		$this->em->persist($newMember);
		$this->em->flush();

		$this->connectCache->deleteMultiple(
			[
				$body['uid'] . ':marketing:accessibleLocations',
				$body['uid'] . ':location:accessibleLocations'
			]
		);

		return Http::status(200, 'USER_ACCESS_CREATED');
	}

	public function getMembersThatHaveAccess(string $serial)
	{
		$admin = $this->em->getRepository(NetworkAccess::class)->findOneBy(
			[
				'serial' => $serial
			]
		);

		$members = $this->em->createQueryBuilder()
			->select('u.uid, u.email, u.first, u.last, u.company, u.admin, o.memberId, o.memberKey')
			->from(OauthUser::class, 'u')
			->leftJoin(NetworkAccessMembers::class, 'o', 'WITH', 'u.uid = o.memberId AND o.memberKey = :mk')
			->where('u.admin = :admin') // TODO OrgId replace
			->andWhere('u.deleted = :bool')
			->setParameter('mk', $admin->memberKey)
			->setParameter('admin', $admin->admin)
			->setParameter('bool', false)
			->getQuery()
			->getArrayResult();
		if (empty($members)) {
			return Http::status(204, 'NO_MEMBERS_YET');
		}

		$formatted = [];

		foreach ($members as $key => $member) {
			if (is_null($member['memberId'])) {
				$members[$key]['enabled'] = false;
			} else {
				$members[$key]['enabled'] = true;
			}
			$formatted[] = $members[$key];
		}


		return Http::status(200, $formatted);
	}

	public function updateOrDeleteUser(string $uid, array $accessKeys)
	{
		$member = $this->em->createQueryBuilder()
			->select('u.memberKey, u.serial')
			->from(NetworkAccess::class, 'u')
			->join(NetworkAccessMembers::class, 'm', 'WITH', 'u.memberKey = m.memberKey')
			->where('m.memberId = :id')
			->setParameter('id', $uid)
			->getQuery()
			->getArrayResult();

		$alreadyAddedSerial = _oAuth2Controller::serialsToArray($member);
		$newSerials         = [];

		foreach ($accessKeys as $serial) {
			if (array_search($serial, $alreadyAddedSerial) === false) {
				$select = $this->em->createQueryBuilder()
					->select('o.memberKey')
					->from(NetworkAccess::class, 'o')
					->where('o.serial = :serial')
					->setParameter('serial', $serial)
					->getQuery()
					->getArrayResult();

				if (!empty($select)) {
					$newAccessMember = new NetworkAccessMembers($uid, $select[0]['memberKey']);

					$this->em->persist($newAccessMember);
					$this->em->flush();

					array_push($newSerials, $serial);
				}
			}
		}

		$toDelete = array_diff($alreadyAddedSerial, $accessKeys);

		if (!empty($toDelete)) {
			$val = [];
			foreach ($member as $key => $value) {
				if (array_search($value['serial'], $toDelete) !== false) {
					$val[] = $value['memberKey'];
				}
			}

			$this->em->createQueryBuilder()
				->delete(NetworkAccessMembers::class, 'p')
				->where('p.memberKey IN (:todeleteKey)')
				->setParameter('todeleteKey', $val)
				->getQuery()
				->execute();

			$this->connectCache->delete($uid . ':profile');
		}

		$this->mp->identify($uid)->track('Updated Access');

		return Http::status(
			200,
			[
				'deleted'  => $toDelete,
				'inserted' => $newSerials
			]
		);
	}

	/**
	 * @param array $body
	 * @param Request $request
	 * @return array
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws \Throwable
	 */
	public function createUser(array $body, Request $request)
	{
		$token = $this->auth->checkToken($request);

		if (!isset($body['email'])) {
			return Http::status(400, 'EMAIL_MISSING');
		}

		if ($this->memberValidationController->isValid($body)['status'] !== 200) {
			return Http::status(409, 'EMAIL_NOT_VALID');
		}

		$password = null;
		$admin    = null;
		$reseller = null;

		$reset = false;

		if ($token) {
			$user = $request->getAttribute('accessUser');
			if ($user['role'] === Role::LegacyAdmin) {
				$admin    = $user['uid'];
				$reseller = $user['reseller'];
				unset($body['admin']);
			} elseif ($user['role'] === Role::LegacyReseller) {
				$reseller = $user['uid'];
			} elseif ($user['role'] === Role::LegacySuperAdmin) {

				if ($body['role'] === role::LegacyReseller && !isset($body['reseller'])) {
					$reseller = 'fc34eaf5-4a01-4c29-be45-0d112847a21c';
				} elseif (!isset($body['reseller'])) {
					return Http::status(400, 'RESELLER_EMPTY');
				} else {
					$reseller = $body['reseller'];
				}
			}
		} else {
			if (isset($body['reseller'])) {
				$reseller = $body['reseller'];
			}
		}

		$accessKeys = [];
		if (isset($body['access'])) {
			$accessKeys = $body['access'];
			unset($body['access']);
		}
		if (array_key_exists('password', $body)) {
			$password = $body['password'];
		}
		if ($password === null || $password === "") {
			$password = Uuid::uuid4()->toString();
			$reset    = true;
		}

		$create = new OauthUser(
			$body['email'],
			$password,
			null,
			$reseller,
			null,
			null,
			null
		);

		$oauthKeys = OauthUser::$KEYS;

		foreach ($body as $key => $value) {
			if (in_array($key, $oauthKeys) && $key !== "password") {
				$create->$key = $value;
			}
		}

		if (!is_null($admin)) {
			$create->admin = $admin;
		}

		$create->created = new DateTime("now");

		$this->em->persist($create);

		$this->em->flush();

		if ($reset === true) {
			$newPasswordController = new _PasswordController($this->em);
			$newPasswordController->forgotPassword($create->email, $create->uid, 'connect');
		}

		$this->signUpForDefaultNotifications($create);

		if ($create->role >= Role::LegacyModerator) {
			$this->updateOrDeleteUser($create->uid, $accessKeys);
		}
		if ($request->getAttribute('user') !== null) {
			$request = $this->locationAccessChangeRequestProvider->make($request, $create);
			$this->locationService->updateUserLocationAccess($request);
		}

		$newNotiList = new UserNotificationLists($create->uid);
		$this->em->persist($newNotiList);
		$this->em->flush();

		return Http::status(200, $create);
	}


	private function signUpForDefaultNotifications(OauthUser $user)
	{
		if ($user->role === 2) {
			$billingErrorNotifyConnect               = new NotificationType($user->uid, 'connect', 'billing_error');
			$billingErrorNotifyEmail                 = new NotificationType($user->uid, 'email', 'billing_error');
			$billingErrorNotifyEmail->additionalInfo = $user->email;


			$invoiceReadyConnect               = new NotificationType($user->uid, 'connect', 'billing_invoice_ready');
			$invoiceReadyEmail                 = new NotificationType($user->uid, 'email', 'billing_invoice_ready');
			$invoiceReadyEmail->additionalInfo = $user->email;

			$cardExpiryConnect               = new NotificationType($user->uid, 'connect', 'card_expiry_reminder');
			$cardExpiryEmail                 = new NotificationType($user->uid, 'email', 'card_expiry_reminder');
			$cardExpiryEmail->additionalInfo = $user->email;


			$this->em->persist($billingErrorNotifyConnect);
			$this->em->persist($billingErrorNotifyEmail);
			$this->em->persist($invoiceReadyConnect);
			$this->em->persist($invoiceReadyEmail);
			$this->em->persist($cardExpiryConnect);
			$this->em->persist($cardExpiryEmail);
		}

		$reviewReceivedConnect               = new NotificationType($user->uid, 'connect', 'review_received');
		$reviewReceivedEmail                 = new NotificationType($user->uid, 'email', 'review_received');
		$reviewReceivedEmail->additionalInfo = $user->email;


		$weeklyReportsConnect               = new NotificationType($user->uid, 'connect', 'insight_weekly');
		$weeklyReportsEmail                 = new NotificationType($user->uid, 'email', 'insight_weekly');
		$weeklyReportsEmail->additionalInfo = $user->email;

		$this->em->persist($reviewReceivedConnect);
		$this->em->persist($reviewReceivedEmail);
		$this->em->persist($weeklyReportsEmail);
		$this->em->persist($weeklyReportsConnect);
	}

	public function createTrialFromForm(Request $request, array $body)
	{
		if (!isset($body['email'])) {
			return Http::status(400, 'EMAIL_MISSING');
		}

		$emailValidator = new EmailValidator($this->em);
		if (!$emailValidator->connectCheck($body['email'])) {
			return Http::status(409, 'EMAIL_NOT_VALID');
		}

		if (!isset($body['vendor'])) {
			return Http::status(400, 'VENDOR_MISSING');
		}

		$body['vendor'] = trim($body['vendor']);
		$vendorList     = [
			'Cisco Meraki'        => 'meraki',
			'Ubiquiti UniFi'      => 'unifi',
			'Openmesh'            => 'openmesh',
			'Ignitenet'           => 'ignitenet',
			'Aerohive'            => 'aerohive',
			'Ruckus Smartzone'    => 'ruckus-smartzone',
			'Ruckus Unleashed'    => 'ruckus-unleashed',
			'Ruckus ZoneDirector' => 'ruckus',
			'Ligowave'            => 'ligowave'
		];

		$vendor = $vendorList[$body['vendor']];

		$now = new DateTime();

		$create = $this->em->getRepository(OauthUser::class)->findOneBy(
			[
				'email' => $body['email']
			]
		);

		$inChargeBee = false;
		if (is_null($create)) {
			$create = new OauthUser(
				$body['email'],
				sha1($body['email'] . $now->format('Y--m-d H:i:s')),
				null,
				'fc34eaf5-4a01-4c29-be45-0d112847a21c',
				null,
				null,
				null
			);

			$this->em->persist($create);

			$dailyReportsConnect               = new NotificationType($create->uid, 'connect', 'insight_daily');
			$dailyReportsEmail                 = new NotificationType($create->uid, 'email', 'insight_daily');
			$dailyReportsEmail->additionalInfo = $create->email;
			$this->em->persist($dailyReportsEmail);
			$this->em->persist($dailyReportsConnect);

			$this->signUpForDefaultNotifications($create);

			$this->em->flush();
		}

		$paramsToMerge = [
			'client_id'     => 'connect',
			'response_type' => 'code',
			'state'         => 11,
			'redirect_uri'  => 'https://product.stampede.ai/code',
			'email'         => $body['email']
		];

		if ($request->getServerParam('SERVER_NAME') === 'api.stage.blackbx.io' || $request->getServerParam('SERVER_NAME') === 'localhost') {
			$paramsToMerge['client_id']    = 'connect_stage';
			$paramsToMerge['redirect_uri'] = 'https://connect.stage.stampede.ai/code';
		}

		$request = $request->withQueryParams(array_merge($request->getQueryParams(), $paramsToMerge));

		$generateMagicLinkController = new _oAuth2TokenController($this->server, $this->em);


		$authCode  = $generateMagicLinkController->authCode(
			$request,
			null,
			$body['email'],
			'email',
			false
		);
		$magicLink = $authCode['message']['redirect_uri'];
		$user      = [
			'customer'            => $create->uid,
			'description'         => 'Trial For ' . $create->email,
			'subscriptions_items' => [
				'method'      => [
					'name' => strtoupper($vendor)
				],
				'planId'      => 'all-in',
				'trial'       => true,
				'redirectUrl' => $magicLink,
				'embed'       => false
			]
		];
		$quote     = $this
			->quoteCreator
			->createQuote($user, $create->uid);

		if ($quote['status'] !== 200) {
			return $quote;
		}

		$mailer     = new _MailController($this->em);
		$mailerSend = $mailer->send(
			[
				[
					'to'   => $body['email'],
					'name' => ''
				]
			],
			[
				'link' => 'https://api.stampede.ai/public/quote/' . $quote['message']
			],
			'SignUp',
			'Getting Started'
		);

		if ($mailerSend['status'] !== 200) {
			return $mailerSend;
		}

		return Http::status(200);
	}

	public function search(Request $request, Response $response)
	{
		$offset = $request->getQueryParam('offset', 0);
		$limit  = min(50, $request->getQueryParam('limit', 50));
		$search = $request->getQueryParam('query', null);
		$users  = $this->memberService->search($offset, $limit, $search);
		return $response->withJson(Http::status(200, $users), 200);
	}
}
