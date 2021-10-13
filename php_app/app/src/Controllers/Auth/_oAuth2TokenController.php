<?php

namespace App\Controllers\Auth;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Models\OauthAccessTokens;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\RequestUser\UserProvider;
use App\Utils\Http;
use App\Utils\HttpFoundationFactorySubClass;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use OAuth2;
use OAuth2\HttpFoundationBridge\Request as BridgeRequest;
use OAuth2\Server;
use OAuth2\Server as Oauth;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Class _oAuth2TokenController
 */
final class _oAuth2TokenController
{

	/**
	 * @var Oauth
	 */
	private $oAuth2server;
	private $em;

	public function __construct(Server $server, EntityManager $em)
	{
		$this->oAuth2server = $server;
		$this->em           = $em;
	}

	public function isLoggedInRoute(Request $request, Response $response)
	{
		$provider = new UserProvider($this->em);
		$user = $provider->getOauthUser($request);

		return $response->withJson(Http::status(200, $user), 200);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return mixed
	 */
	public function token(Request $request, Response $response)
	{
		// convert a request from PSR7 to httpFoundation
		$httpFoundationFactory = new HttpFoundationFactorySubClass();
		$symfonyRequest        = $httpFoundationFactory->createRequest($request);
		$bridgeRequest         = BridgeRequest::createFromRequest($symfonyRequest);

		$resp = new OAuth2\Response();

		$this->oAuth2server->handleTokenRequest($bridgeRequest, $resp);
		$status   = $resp->getStatusCode();
		$response = $response->withStatus($status);

		if ($status === 200) {
			$body = $request->getParsedBody();
			if (empty($body)) {
				$body = $request->getQueryParams();
			}

			if (
				$body['client_id'] === 'connect' ||
				$body['client_id'] === 'connect_stage' ||
				$body['client_id'] === 'stampede.ai.connect' ||
				$body['client_id'] === 'beta' ||
				$body['client_id'] === 'master'
			) {
				$user = $this->em->getRepository(OauthAccessTokens::class)->findOneBy(
					[
						'accessToken' => $resp->getParameter('access_token')
					]
				);

				$mp = new _Mixpanel();
				$mp->increment($user->userId, 'logged_in', 1);
			}
		}

		$this->em->clear();

		return $response->write(
			json_encode($resp->getParameters())
		);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return mixed
	 */
	public function authorize(Request $request, Response $response)
	{
		$user = $request->getAttribute('user');

		if (isset($user['profileId'])) {
			$code = $this->authCode($request, $response, $user['profileId'], 'id', true);
		} else {
			$code = $this->authCode($request, $response, $user['uid'], 'uid', false);
		}

		$this->em->clear();

		return $response->withJson($code, $code['status']);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $param - UID OR EMAIL
	 * @param string $type 'uid'/'email'
	 * @param bool $nearly
	 * @return array
	 */

	public function authCode(Request $request, ?Response $response, $param, string $type, bool $nearly)
	{

		if ($nearly) {
			$nearlyUser = $this->em->getRepository(UserProfile::class)->findOneBy(
				[
					$type => $param
				]
			);

			if (is_null($nearlyUser)) {

				return Http::status(404);
			}
		} else {
			$existingOauth = $this->em->getRepository(OauthUser::class)
				->findOneBy(
					[
						$type => $param
					]
				);

			if (is_null($existingOauth)) {

				return Http::status(404);
			}
		}


		/**
		 * Convert PSR7 Request to OAuth request
		 */
		$httpFoundationFactory = new HttpFoundationFactorySubClass();
		$symfonyRequest        = $httpFoundationFactory->createRequest($request);
		$bridgeRequest         = BridgeRequest::createFromRequest($symfonyRequest);

		$resp = new OAuth2\Response();

		if ($nearly) {
			$this->oAuth2server->handleAuthorizeRequest($bridgeRequest, $resp, true, $nearlyUser->id);
		} else {
			$this->oAuth2server->handleAuthorizeRequest($bridgeRequest, $resp, true, $existingOauth->uid);
		}


		$uri = $resp->getHttpHeader('Location');

		if ($resp->getStatusCode() !== 302) {
			return Http::status($resp->getStatusCode(), $resp->getResponseBody());
		}

		return Http::status(
			$resp->getStatusCode(),
			[
				'redirect_uri' => $uri
			]
		);
	}

	public function getCodeFromLink(string $link)
	{
		$url_components = parse_url($link);
		parse_str($url_components['query'], $params);
		return $params['code'];
	}

	public function legacyAuth(OAuth2\Response $resp, $uid = '')
	{
		$body = [
			'uid'     => $uid,
			'token'   => $resp->getParameter('access_token'),
			'expires' => time() + $resp->getParameter('expires_in')
		];

		$curl = new Curl();
		$curl->post('https://engine.stampede.ai/auth', $body);

		return $curl->response;
	}
}
