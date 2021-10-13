<?php

/**
 * Created by jamieaitken on 23/11/2017 at 12:36
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Utils;


use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Notifications\FirebaseCloudMessagingController;
use App\Controllers\SMS\_SMSController;
use App\Models\Locations\LocationSettings;
use App\Models\Members\OAuthUserSMS;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\Notifications\Notification;
use App\Models\Notifications\NotificationSettings;
use App\Models\Notifications\NotificationType;
use App\Models\Notifications\UserNotificationLists;
use App\Models\Notifications\UserNotifications;
use App\Models\OauthUser;
use App\Package\Auth\UserContext;
use App\Package\Auth\UserSource;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;

class PushNotifications
{
	protected $em;
	protected $mail;
	protected $connectCache;
	/**
	 * @var LocationService
	 */
	private $locationService;

	private $organisationService;

	public function __construct(EntityManager $em)
	{
		$this->em           = $em;
		$this->mail         = new _MailController($this->em);
		$this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
		$this->locationService = new LocationService($em);
		$this->organisationService = new OrganizationService($this->em);
	}

	/**
	 * @param Notification $newNotification
	 * @param $sendKind
	 * @param null $user
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	public function testRoute(Request $request, Response $response)
	{


		$bodyNotification = $request->getParsedBodyParam('notification');

		$newNotifcation = new Notification('testSend', $bodyNotification['title'], $bodyNotification['kind'], '');


		$send = $this->pushNotification($newNotifcation, 'specific', ['uid' => $request->getParsedBodyParam('uid')]);

		return $response->withJson([], 200);
	}

	public function getIpRoute(Request $request, Response $response)
	{
		$roleChecker = new UserRoleChecker($this->em);
		$userProvider = new UserProvider($this->em);
		//$user = $userProvider->getOauthUser($request);
		$user = $request->getAttribute(UserContext::class);
		$source = $request->getAttribute(UserSource::class);

		//return $response->withJson($source->getUser());
		//$ip_server = $_SERVER;

		//return $response->withJson(Http::status(200, $ip_server), 200);
	}

	public function pushNotification(Notification $newNotification, $sendKind, $user = null, ?string $message = null)
	{
		if ($sendKind === 'all') {
			$this->sendToLists($newNotification, $message);
		} elseif ($sendKind === 'specific' && !is_null($user)) {
			$this->sendToSpecificList($user, $newNotification, $message);
		}
	}

	/**
	 * @param Notification $notification
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	private function sendToLists(Notification $notification, ?string $message = null)
	{
		$getUidsOfListsToSend = $this->em->createQueryBuilder()
			->select('u.uid')
			->from(NotificationType::class, 'u')
			->where('u.notificationKind = :kind')
			->setParameter('kind', $notification->kind)
			->groupBy('u.uid')
			->getQuery()
			->getArrayResult();

		foreach ($getUidsOfListsToSend as $key => $value) {
			$this->sendToSpecificList(['uid' => $value], $notification, $message);
		}
	}

	/**
	 * @param array $user
	 * @param Notification $notification
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	private function sendToSpecificList(array $user, Notification $notification, ?string $message = null)
	{
		$userLookUp = $this->em->getRepository(UserNotificationLists::class)->findOneBy([
			'uid' => $user['uid']
		]);

		if (is_object($userLookUp)) {
			$newUserNotification = new UserNotifications($userLookUp->notificationList, $notification->id);
			$this->em->persist($newUserNotification);
			$userLookUp->hasSeen = false;
		} else {
			$newSub              = $this->createNewSubscriber($user);
			$newUserNotification = new UserNotifications($newSub['notificationList'], $notification->id);
			$this->em->persist($newUserNotification);
		}

		$this->em->flush();

		$this->connectCache->delete($user['uid'] . ':connectNotificationLists');

		$sendEmailQuery = $this->em->createQueryBuilder()
			->select('u.additionalInfo')
			->from(NotificationType::class, 'u')
			->where('u.uid = :ui')
			->andWhere('u.type = :email')
			->andWhere('u.notificationKind = :kind')
			->setParameter('ui', $user['uid'])
			->setParameter('email', 'email')
			->setParameter('kind', $notification->kind)
			->getQuery()
			->getArrayResult();

		if (!empty($sendEmailQuery)) {
			$this->sendToEmail($notification, $sendEmailQuery[0]['additionalInfo'], $message);
		}

		$firebaseNotificationsController = new FirebaseCloudMessagingController($this->em);
		$firebaseNotificationsController->sendMessage($user['uid'], $notification);
	}

	/**
	 * @param array $user
	 * @return array
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */

	private function createNewSubscriber(array $user)
	{
		$newUserNotification = new UserNotificationLists($user['uid']);
		$this->em->persist($newUserNotification);
		$this->em->flush();

		return $newUserNotification->getArrayCopy();
	}

	/**
	 * @param Notification $notification
	 * @param $email
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	private function sendToEmail(Notification $notification, $email, ?string $message = null)
	{
		$args = $notification->getArrayCopy();
		if (is_null($args['serial'])) {
			$args['serial'] = '';
		}

		$args['title'] =  $notification->title;
		if (is_null($message)) {
			$args['message'] = $notification->getMessage();
		} else {
			$args['message'] = $message;
		}

		$args['link'] =  $notification->getProductRoute();
		$this->mail->send(
			[
				[
					'to'   => $email,
					'name' => $email
				]
			],
			$args,
			'NotificationTemplate',
			$notification->title
		);
	}

	/**
	 * @param string $orgId
	 * @param string $eventKey
	 * @return array
	 */

	public function getMembersByOrgId(string $orgId, string $eventKey)
	{
		$organisation = $this->organisationService->getOrganisationById($orgId);
		$users = $this->organisationService->whoCanAccessOrganization($organisation);

		$formattedUids = array_keys($users);

		$oauthUserInfo = $this->em->createQueryBuilder()
			->select('DISTINCT(o.uid) as uid')
			->from(NotificationType::class, 'u')
			->leftJoin(OauthUser::class, 'o', 'WITH', 'u.uid = o.uid')
			->andWhere('u.uid IN (:uid)')
			->andWhere('u.notificationKind = :kind')
			->setParameter('kind', $eventKey)
			->setParameter('uid', $formattedUids)
			->getQuery()
			->getArrayResult();


		return $oauthUserInfo;
	}

	/**
	 * @param string $serial
	 * @param string $eventKey
	 * @return array|false|mixed
	 * @throws Exception
	 */
	public function getMembersViaSerial(string $serial, string $eventKey)
	{

		$fetch = $this->connectCache->fetch($serial . ':membersNotify:' . $eventKey);
		if (!is_bool($fetch)) {
			return $fetch;
		}

		/** @var LocationSettings $location */
		$location = $this
			->em
			->getRepository(LocationSettings::class)
			->findOneBy(['serial' => $serial]);

		if ($location === null) {
			throw new Exception("cannot find location for serial ($serial)");
		}

		$users = $this->locationService->whoCanAccessLocation($location);
		$formattedUids = array_keys($users);

		$oauthUserInfo = $this->em->createQueryBuilder()
			->select('DISTINCT(o.uid) as uid')
			->from(NotificationType::class, 'u')
			->leftJoin(OauthUser::class, 'o', 'WITH', 'u.uid = o.uid')
			->andWhere('u.uid IN (:uid)')
			->andWhere('u.notificationKind = :kind')
			->setParameter('kind', $eventKey)
			->setParameter('uid', $formattedUids)
			->getQuery()
			->getArrayResult();

		$this->connectCache->save($serial . ':membersNotify:' . $eventKey, $oauthUserInfo);

		return $oauthUserInfo;
	}

	/**
	 * @param string $serial
	 * @param string $eventKey
	 * @return array|false|mixed
	 * @throws Exception
	 */
	public function getMembersAndAliasViaSerial(string $serial, string $eventKey)
	{
		$fetch = $this->connectCache->fetch($serial . ':membersAliasNotify:' . $eventKey);
		if (!is_bool($fetch)) {
			return $fetch;
		}

		/** @var LocationSettings $location */
		$location = $this
			->em
			->getRepository(LocationSettings::class)
			->findOneBy(['serial' => $serial]);

		if ($location === null) {
			throw new Exception("cannot find location for serial ($serial)");
		}

		$users = $this->locationService->whoCanAccessLocation($location);
		$formattedUids = array_keys($users);

		$oauthUserInfo = $this->em->createQueryBuilder()
			->select('o.uid')
			->from(NotificationType::class, 'u')
			->join(OauthUser::class, 'o', 'WITH', 'u.uid = o.uid')
			->where('u.uid IN (:uid)')
			->andWhere('u.notificationKind = :kind')
			->setParameter('kind', $eventKey)
			->setParameter('uid', $formattedUids)
			->getQuery()
			->getArrayResult();

		$dataStructure = [
			'alias'         => $location->getAlias(),
			'oauthUserInfo' => $oauthUserInfo
		];

		if (!empty($oauthUserInfo)) {
			$this->connectCache->save($serial . ':membersAliasNotify:' . $eventKey, $dataStructure);
		}

		return $dataStructure;
	}

	private function sendToSMS($notification, $phoneNumber)
	{
		$newSMS = new _SMSController($this->em);
		$newSMS->send([
			'number'  => $phoneNumber,
			'message' => $notification['title'] . ' ' . $notification['kind'],
			'sender'  => 'Notification Service'
		]);
	}

	private function sendToEmailAndSMS($uid, $notification, $email, $sms)
	{
		$this->sendToEmail($notification, $email);
		$this->sendToSMS($notification, $sms);
	}
}
