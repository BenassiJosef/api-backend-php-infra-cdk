<?php

namespace App\Policy;

use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\User;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

use App\Controllers\Auth\_oAuth2Controller;
use App\DataAccess\_oAuth2_CustomStorage;
use App\Models\OauthClients;

class Auth
{

	protected $auth;
	protected $em;

	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;


	/**
	 * @var UserProvider $userProvider
	 */
	private $userProvider;

	/**
	 * @var OauthClients $oauthClients
	 */
	private $oauthClients;

	public function __construct(_oAuth2Controller $auth, EntityManager $em)
	{
		$this->auth = $auth;
		$this->em   = $em;
		$this->userRoleChecker = new UserRoleChecker($em);
		$this->userProvider = new UserProvider($em);
		$this->oauthClients = new OauthClients();
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		// check that we have a valid token

		if ($this->auth->validateToken($request)) {
			$arguments = $request->getAttribute('route')->getArguments();
			$body      = $request->getParsedBody();

			$clientId = "";
			if (is_array($body) && array_key_exists('client_id', $body)) {
				$clientId = $body['client_id'];
			}
			$this->oauthClients->setClientId($clientId);
			// handle users of my.stampede.ai
			if ($this->oauthClients->isEndUserClient()) {
				$request = $request->withAttribute('user', [
					'valid'     => true,
					'profileId' => intval($this->auth->getUid())
				]);
			} else {
				// normal users - connect and master
				$user = $this->auth->currentUser();
				if (isset($arguments['uid'])) {
					if ($arguments['uid'] === 'me') {
						// the user is accessing the site as themselves
						$request = $request->withAttribute('userId', $user['uid']);
					} else {
						// the user is accessing the site as someone else - TODO - shouldbn't we check they are allowed to do this?
						$request = $request->withAttribute('userId', $arguments['uid']);
					}
				}
				// store the current user in the request
				$request = $request->withAttribute('user', $user);
			}
			// check the user has access to the requested organisation
			$orgId = $request->getAttribute("orgId");
			if ($orgId !== null) {
				$user            = $this->userProvider->getOauthUser($request);
				$organizationIds = $this->userRoleChecker->organizationIds($user);
				if (!array_key_exists($orgId, $organizationIds)) {
					return $response->withStatus(403, "You do not have access to this organization");
				}
			}
			return $next($request, $response);
		}
		return $response->withStatus(403, "Invalid token");
	}
}
