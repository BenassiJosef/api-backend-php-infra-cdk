<?php

/**
 * Created by patrickclover on 27/12/2017 at 12:15
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Auth;

use App\Controllers\Integrations\Mail\_MailController;
use App\Models\OauthClients;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use OAuth2\Server;
use Slim\Http\Response;
use Slim\Http\Request;

class _MagicLink
{
    protected $em;
    protected $auth;
    protected $mail;

    /**
     * @var OauthClients $oauthClient
     */
    protected $oauthClient;

    public function __construct(EntityManager $em, Server $auth)
    {
        $this->em   = $em;
        $this->auth = $auth;
        $this->mail = new _MailController($this->em);
        $this->oauthClient = new OauthClients();
    }

    public function getRoute(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        if (empty($params)) {
            return $response->withJson(Http::status(403), 403);
        }

        if (!isset($params['email'])) {
            return $response->withJson(Http::status(403, 'EMAIL_MISSING'), 403);
        }

        $this->oauthClient->setClientId($params['client_id']);

        $email = $params['email'];
        $oauth = new _oAuth2TokenController($this->auth, $this->em);
        $code  = $oauth->authCode($request, $response, $email, 'email', $this->oauthClient->isEndUserClient());

        if ($code['status'] !== 302) {
            return $response->withJson($code, $code['status']);
        }

        $link = $code['message']['redirect_uri'];
        $send = $this->sendLink($email, $link);

        return $response->withJson($send, $send['status']);
    }

    public function postRoute(Request $request, Response $response)
    {

        $params = $request->getParsedBody();
        if (empty($params)) {
            return $response->withJson(Http::status(403), 403);
        }
        if (!isset($params['uid'])) {
            return $response->withJson(Http::status(403, 'EMAIL_MISSING'), 403);
        }

        $email = $params['uid'];
        $oauth = new _oAuth2TokenController($this->auth, $this->em);
        $code  = $oauth->authCode($request, $response, $email, 'uid', false);

        return $response->withJson($code, $code['status']);
    }

    public function generateNearlyLinkRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withJson(Http::status(403), 403);
        }

        /**
         * User Profile ID Required
         */
        if (!isset($body['id'])) {
            return $response->withJson(Http::status(403), 403);
        }

        $profileId = $body['id'];

        $oauth = new _oAuth2TokenController($this->auth, $this->em);
        $code  = $oauth->authCode($request, $response, $profileId, 'id', true);

        return $response->withJson($code, $code['status']);
    }

    public function sendLink($email, $link)
    {
        $sendStructure = [
            'to'   => '',
            'name' => ''
        ];

        $contentsStructure = [
            'message' => '',
            'link'    => $link
        ];


        if ($this->oauthClient->isEndUserClient()) {
            $nearlyFetch = $this->em->getRepository(UserProfile::class)->findOneBy([
                'email' => $email
            ]);

            if (is_null($nearlyFetch)) {
                return Http::status(404);
            }

            $sendStructure['to']   = $email;
            $sendStructure['name'] = $email;

            $contentsStructure['message'] = 'You asked us to send you a magic link so you view your data.';
            $subject                      = 'View your Data';

            if ($this->oauthClient->isLoyaltyClient()) {
                $contentsStructure['message'] = 'Get started with your loyalty account by clicking the magic lonk';
                $subject                      = 'A world of loyalty awaits';
            }
        } else {
            $existingOauth = $this->em->getRepository(OauthUser::class)->findOneBy([
                'email' => $email
            ]);

            if (is_null($existingOauth)) {
                return Http::status(404);
            }

            $sendStructure['to']   = $email;
            $sendStructure['name'] = $existingOauth->fullName();

            $contentsStructure['message'] = 'You asked us to send you a magic link for quickly signing in to your Stampede account.';
            $subject                      = 'Magic sign-in link for ' . $existingOauth->company . ' on Stampede';
        }

        $this->em->clear();

        return $this->mail->send(
            [
                $sendStructure
            ],
            $contentsStructure,
            'MagicLink',
            $subject
        );
    }
}
