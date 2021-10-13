<?php

namespace App\Controllers\Auth;

use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Package\Organisations\UserRoleChecker;
use App\Utils\CacheEngine;
use App\Utils\HttpFoundationFactorySubClass;
use Doctrine\ORM\EntityManager;
use OAuth2\HttpFoundationBridge\Request as BridgeRequest;
use phpDocumentor\Reflection\Types\Void_;
use Slim\Http\Response;
use Slim\Http\Request;
use Psr\Log\LoggerInterface;
use App\Models\OauthUser;
use OAuth2\Server;

/**
 * Class _Controller_oAuth2
 */
class _oAuth2Controller
{

	/**
	 * @var Server $oAuth2server
	 */
	protected $oAuth2server;

	protected $user;

	protected $em;

	protected $client;

	protected $connectCache;

	protected $logger;

	protected $userRoleChecker;

	/**
	 * _oAuth2Controller constructor.
	 * @param LoggerInterface $logger
	 * @param $server
	 * @param EntityManager $em
	 */

	public function __construct(LoggerInterface $logger, Server $server, EntityManager $em, UserRoleChecker $userRoleChecker)
	{
		$this->logger          = $logger;
		$this->oAuth2server    = $server;
		$this->em              = $em;
		$this->userRoleChecker = $userRoleChecker;
		$this->connectCache    = new CacheEngine(getenv('CONNECT_REDIS'));
	}


	public function getUid()
	{
		return $this->user;
	}

	/**
	 * @return mixed
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @param array $array
	 * @return array
	 */

	public static function serialsToArray($array = [])
	{

		$val = [];
		foreach ($array as $key => $value) {
			$val[] = $value['serial'];
		}

		return $val;
	}

	protected function superAdminAccess()
	{
		$qb = $this->em->createQueryBuilder();

		$serials = $qb->select('a.serial')
			->from(NetworkAccess::class, 'a')
			->getQuery()
			->getArrayResult();

		return _oAuth2Controller::serialsToArray($serials);
	}

	/**
	 * @param string $uid
	 * @param int $role
	 * @return array
	 */

	public function adminPartnerAccess($uid = '', $role = 0)
	{
		if ($role === 1) {
			$type = 'reseller';
		} elseif ($role === 2) {
			$type = 'admin';
		} else {
			/** FIX ME */
			return [];
		}

		$serials = $this->em->createQueryBuilder()
			->select('a.serial')
			->from(NetworkAccess::class, 'a')
			->where('a.' . $type . ' = :uid')
			->setParameter('uid', $uid)
			->getQuery()
			->getArrayResult();

		return _oAuth2Controller::serialsToArray($serials);
	}

	public function memberAccess($uid = '')
	{

		$serials = $this->em->createQueryBuilder()
			->select('a.serial')
			->from(NetworkAccess::class, 'a')
			->join(NetworkAccessMembers::class, 'm', 'WITH', 'a.memberKey = m.memberKey')
			->where('m.memberId = :uid')
			->setParameter('uid', $uid)
			->getQuery()
			->getArrayResult();

		return _oAuth2Controller::serialsToArray($serials);
	}

	public function currentUser()
	{
		$uid = $this->getUid();

		$fetch = $this->connectCache->fetch($uid . ':profile');

		if (!is_bool($fetch)) {
			if (isset($fetch['access'])) {
				//	return $fetch;
			}
		}

		/**
		 * @var OauthUser $u
		 */
		$u = $this
			->em
			->getRepository(OauthUser::class)
			->findOneBy(
				['uid' => $uid]
			);

		if (is_null($u)) {
			return [];
		}

		$user = $u->jsonSerialize();

		$access = $this->userRoleChecker->locationSerials($u);

		$user['access'] = $access;

		$this->connectCache->save($uid . ':profile', $user);

		return $user;
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	public function validateToken(Request $request): bool
	{
		// convert a request from PSR7 to hhtpFoundation
		$httpFoundationFactory = new HttpFoundationFactorySubClass();
		$symfonyRequest        = $httpFoundationFactory->createRequest($request);
		$bridgeRequest         = BridgeRequest::createFromRequest($symfonyRequest);

		if (!$this->oAuth2server->verifyResourceRequest($bridgeRequest)) {
			$this->oAuth2server->getResponse()->send();
			die;
		}

		// store the user_id
		$token        = $this->oAuth2server->getAccessTokenData($bridgeRequest);
		$this->user   = $token['user_id'];
		$this->client = $token['client_id'];

		return true;
	}

	public function checkToken($request)
	{
		// convert a request from PSR7 to hhtpFoundation
		$httpFoundationFactory = new HttpFoundationFactorySubClass();
		$symfonyRequest        = $httpFoundationFactory->createRequest($request);
		$bridgeRequest         = BridgeRequest::createFromRequest($symfonyRequest);

		if (!$this->oAuth2server->verifyResourceRequest($bridgeRequest)) {
			return false;
		}

		// store the user_id
		$token      = $this->oAuth2server->getAccessTokenData($bridgeRequest);
		$this->user = $token['user_id'];

		return true;
	}

	// needs an oAuth2 Client credentials grant
	// with Resource owner credentials grant alseo works
	public function getAll(Request $request, Response $response, $args)
	{
	}

	// needs an oAuth2 Client credentials grant
	// with Resource owner credentials grant alseo works
	public function get(Request $request, Response $response, $args)
	{
	}

	// needs an oAuth2 Resource owner credentials grant
	// checked with isset($this->user)
	public function add(Request $request, Response $response, $args)
	{
		if ($this->validateToken($request) && isset($this->user)) {
		} else {
			return $response->withStatus(400);
		}
	}

	// needs an oAuth2 Resource owner credentials grant
	// checked with isset($this->user)
	public function update(Request $request, Response $response, $args)
	{
		if ($this->validateToken($request) && isset($this->user)) {
		} else {
			return $response->withStatus(400);
		}
	}

	// needs an oAuth2 Resource owner credentials grant
	// checked with isset($this->user)
	public function delete(Request $request, Response $response, $args)
	{
		if ($this->validateToken($request) && isset($this->user)) {
		} else {
			return $response->withStatus(400);
		}
	}
}
