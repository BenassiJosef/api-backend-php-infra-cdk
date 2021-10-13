<?php

/**
 * Created by patrickclover on 24/12/2017 at 20:00
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Auth;

use App\Models\Auth\Provider;
use App\Models\OauthClients;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use OAuth2\Server;
use Slim\Http\Response;
use Slim\Http\Request;

class _GoogleOauthClient
{

    public $url = 'https://api.stampede.ai/';
    public $clientId = '348453694666-qjs5mp09q9p6l7n8rp7c5atodj5atvbp.apps.googleusercontent.com';
    public $clientSecret = 'hqzHvys-2q70xFG3zZkrMRP-';
    public $androidClientId = '348453694666-hnihov7l2s9to6g7rs9e5dakf7h2q0r9.apps.googleusercontent.com';
    public $iOSClientId = '348453694666-k8lfb1i64moqr3dlrebb9nur1q1a2ggp.apps.googleusercontent.com';

    public $androidLoyaltyClientId = '212192959301-nf70644tbnnujf972o12tp5sttfahcev.apps.googleusercontent.com';
    public $iosLoyaltyClientId = '212192959301-c867b5jt03jbbgpdrdec1acr7d0vjt82.apps.googleusercontent.com';
    public $loyaltySecret = 'jcS-qOU85dBo0wMzMsx9BOE9';

    private $infrastructureCache;
    private $em;
    private $auth;

    /**
     * @var OauthClients $oauthClient
     */
    protected $oauthClient;

    public function __construct(Server $auth, EntityManager $em)
    {
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->em                  = $em;
        $this->auth                = $auth;
        $this->oauthClient = new OauthClients();
    }


    public function getProvider(string $sentClient, ?string $platform = 'android'): Google
    {
        $clientId = $this->clientId;
        $secret = $this->clientSecret;

        if ($sentClient === 'insight_app_android') {
            // $clientId =  $this->androidClientId;
        }
        if ($sentClient === 'insight_app_ios') {
            // $clientId =  $this->iOSClientId;
        }
        if ($sentClient === 'stampede.ai.loyalty_ignore') {
            $secret = $this->loyaltySecret;
            $clientId = $this->androidLoyaltyClientId;
            if ($platform === 'ios') {
                $clientId = $this->iosLoyaltyClientId;
            }
        }
        return new Google([
            'clientId'     => $clientId,
            'clientSecret' => $secret,
            'redirectUri'  => $this->url . 'oauth/google/callback'
        ]);
    }

    public function getLink(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        $sentClient = $params['client_id'];
        $state = Strings::random(20);
        $this->oauthClient->setClientId($sentClient);
        if ($this->oauthClient->isEndUserClient()) {
            $state =  $params['state'];
        }

        $this->infrastructureCache->save('googleLinks:' . $state, $params);

        $provider            = $this->getProvider($params['client_id'], $params['method']);

        $authUrl = $provider->getAuthorizationUrl([
            'state' => $state
        ]);

        $res = Http::status(302, [
            'redirect_uri' => $authUrl
        ]);

        $this->em->clear();

        return $response->withJson($res, $res['status']);
    }

    public function callback(Request $request, Response $response)
    {
        $params       = $request->getQueryParams();

        $requestState = $this->infrastructureCache->fetch('googleLinks:' . $params['state']);
        if ($requestState === false) {
            return $response->withJson(Http::status(404, 'INVALID_STATE'), 404);
        }

        $this->oauthClient->setClientId($requestState['client_id']);
        $redirectUrl = $requestState['redirect_uri'];
        if ($this->oauthClient->isLoyaltyClient()) {
            $redirectUrl = 'ai.stampede.loyalty://code';
        }
        $provider            = $this->getProvider($requestState['client_id'], $requestState['method']);
        $token        = $provider->getAccessToken('authorization_code', [
            'code' => $params['code']
        ]);

        $owner = [];
        try {
            $ownerDetails = $provider->getResourceOwner($token);
            $owner        = $ownerDetails->toArray();
        } catch (IdentityProviderException $e) {
            // Failed to get user details
            return $response->withJson($e->getMessage(), $e->getCode());
        }

        $mappedId = $this->mapUser($ownerDetails->getId(), $owner['emails'][0]['value'], $owner['image']['url']);
        if ($mappedId === false) {
            $this->em->clear();
            if (!$this->oauthClient->isEndUserClient()) {
                return $response->withJson(Http::status(403, 'NO_USER_FOUND'), 403);
            } else {
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', $redirectUrl . '?email=' . $owner['emails'][0]['value']);
            }
        }

        $req = $request
            ->withQueryParams([
                'client_id'     => $requestState['client_id'],
                'response_type' => 'code',
                'state'         => $params['state'],
                'redirect_uri'  => $requestState['redirect_uri']
            ]);

        $oauth = new _oAuth2TokenController($this->auth, $this->em);
        $resp  = $oauth->authCode(
            $req,
            $response,
            $mappedId,
            $this->oauthClient->isEndUserClient() ? 'id' : 'uid',
            $this->oauthClient->isEndUserClient()
        );

        $this->em->clear();

        if ($resp['status'] !== 302) {
            return $response
                ->withJson($resp, $resp['status']);
        }

        if ($this->oauthClient->isLoyaltyClient()) {
            return $response
                ->withStatus(302)
                ->withHeader(
                    'Location',
                    $redirectUrl . '?code=' . $oauth->getCodeFromLink($resp['message']['redirect_uri'])
                );
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', $resp['message']['redirect_uri']);
    }

    /**
     * @param $id
     * @param $email
     * @param $image
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function mapUser($id, $email, $image)
    {
        $existing = $this->em->getRepository(Provider::class)->findOneBy([
            'userId' => $id
        ]);
        if (!$this->oauthClient->isEndUserClient()) {
            /**
             * @var OauthUser $existingOauth
             */
            $existingOauth = $this->em->getRepository(OauthUser::class)->findOneBy([
                'email' => $email
            ]);
            if (is_null($existingOauth)) {
                return false;
            }
            $userId = $existingOauth->getUid();
        } else {
            /**
             * @var UserProfile $userProfile
             */
            $userProfile = $this->em->getRepository(UserProfile::class)->findOneBy([
                'email' => $email
            ]);
            if (is_null($userProfile)) {
                return false;
            }

            $userId = strval($userProfile->getId());
        }

        if (is_object($existing)) {
            return $userId;
        }

        $provider = new Provider($userId, $id, $image, 'google');
        $this->em->persist($provider);
        $this->em->flush();

        return $userId;
    }
}
