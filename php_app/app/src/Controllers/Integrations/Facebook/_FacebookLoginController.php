<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 09/01/2017
 * Time: 18:33
 */

namespace App\Controllers\Integrations\Facebook;

use App\Models\Integrations\Facebook\FacebookOauth;
use App\Models\Integrations\Facebook\FacebookPages;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Slim\Http\Response;
use Slim\Http\Request;

class _FacebookLoginController
{

    public $appId = '1582766385320984';
    public $permissions = [
        'email',
        'manage_pages',
        'pages_show_list'
    ];
    public $absolute_url = 'nearly.online';
    public $callback_uri = 'api.stampede.ai';
    public $redirect_url = 'https://api.stampede.ai/facebook/callback';
    protected $secret = '3b381249f38daf00abafaafe2ef41d07';
    protected $apps = [
        'reviews' => [
            'appId'  => '526012921156845',
            'secret' => '47e6809319b4064b9ae305e2b5ac620c'
        ]
    ];
    public $facebook;
    public $em;
    public $infrastructureCache;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function authorizeRoute(Request $request, Response $response)
    {
        $params = $request->getQueryParams();

        if (!isset($params['app_name'])) {
            return $response->withJson(Http::status(400, 'REQUIRE_APP_NAME'), 400);
        }

        if (!isset($params['redirect_url'])) {
            return $response->withJson(Http::status(400, 'REQUIRE_REDIRECT_URL'), 400);
        }

        $send = $this->authorize(
            $params['app_name'],
            $params['redirect_url'],
            $request->getAttribute('orgId')
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function authorize(string $appName, string $redirectUrl, string $orgId)
    {

        $this->init($appName);
        $helper   = $this->helper();
        $loginUrl = $helper->getLoginUrl($this->redirect_url, $this->permissions);

        $this->infrastructureCache->save('facebookRedirects:' . $this->getState($loginUrl), [
            'app_name'     => $appName,
            'orgId'        => $orgId,
            'redirect_url' => $redirectUrl
        ]);

        return Http::status(200, $loginUrl);
    }

    public function getTokensRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $res   = $this->getTokens($orgId);

        $this->em->clear();

        return $response->withJson($res, $res['status']);
    }

    public function callback(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        $store  = $this->infrastructureCache->fetch('facebookRedirects:' . $params['state']);

        $this->generateLongLived($params['code'], $store['app_name'], $store['orgId']);

        $this->em->clear();

        return $response->withHeader('Location', $store['redirect_url']);
    }

    public function generateLongLived($code, $appName, $orgId)
    {

        $fb = $this->init($appName);

        $oauth                = $fb->getOAuth2Client();
        $accessToken          = $oauth->getAccessTokenFromCode($code, $this->redirect_url);
        $longLivedAccessToken = $oauth->getLongLivedAccessToken($accessToken);
        $debug                = $oauth->debugToken($longLivedAccessToken);

        $account = $fb->get('/me', $longLivedAccessToken);

        $createNew               = new FacebookOauth();
        $createNew->orgId        = $orgId;
        $createNew->appName      = $appName;
        $createNew->accessToken  = $longLivedAccessToken;
        $createNew->issuedAt     = $debug->getField('issued_at');
        $createNew->expiresAt    = $debug->getField('expires_at') === 0 ? null : $debug->getField('expires_at');
        $createNew->accountAlias = $account->getDecodedBody()['name'];

        $this->em->persist($createNew);
        $this->em->flush();

        return $createNew->getArrayCopy();
    }

    public function getTokens(string $orgId)
    {
        $users = $this->em->createQueryBuilder()
            ->select('u.id, u.orgId, u.accessToken, u.expiresAt, u.issuedAt, u.appName, u.accountAlias, i.pageId, i.name')
            ->from(FacebookOauth::class, 'u')
            ->leftJoin(FacebookPages::class, 'i', 'WITH', 'u.id = i.facebookOauthId')
            ->where('u.orgId = :orgId') // TODO OrgId replace
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($users)) {
            return Http::status(404);
        }

        return Http::status(200, $users);
    }

    public function init(string $appName, array $params = [])
    {
        $this->facebook = new Facebook(array_merge([
            'app_id'                  => $this->apps[$appName]['appId'],
            'app_secret'              => $this->apps[$appName]['secret'],
            'default_graph_version'   => 'v3.1',
            'persistent_data_handler' => new _FacebookSessionHandler()
        ], $params));

        return $this->facebook;
    }

    public function helper()
    {
        $helper = $this->facebook->getRedirectLoginHelper();

        return $helper;
    }

    public function getState(string $url)
    {
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        return $query['state'];
    }

    public function handleErrors($req, $params)
    {
        try {
            $res = $req($params);
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            return 'Graph returned an error: ' . $e->getMessage();
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            return 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        return $res;
    }
}
