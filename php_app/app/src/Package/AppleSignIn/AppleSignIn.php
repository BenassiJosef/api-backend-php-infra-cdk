<?php

namespace App\Package\AppleSignIn;

use App\Controllers\Auth\_oAuth2TokenController;
use App\Models\UserProfile;
use App\Utils\Http;
use AppleSignIn\ASDecoder;
use Doctrine\ORM\EntityManager;
use OAuth2\Server;
use Slim\Http\Request;
use Slim\Http\Response;

class AppleSignIn
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Server $auth
     */
    private $auth;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     * @param Server $auth
     */
    public function __construct(
        EntityManager $entityManager,
        Server $auth
    ) {
        $this->entityManager = $entityManager;
        $this->auth = $auth;
    }

    protected $defaultClientId = "online.nearly";

    public function postTokenPath(Request $request, Response $response): Response
    {
        $token = $request->getParsedBodyParam('token');
        $clientId = $request->getParsedBodyParam('client_id', $this->defaultClientId);
        $auth = $request->getParsedBodyParam('auth', false);

        if (!$token) {
            return $response->withStatus(404);
        }
        $appleSignInPayload = ASDecoder::getAppleSignInPayload($token);

        $userProfile = null;
        /**
         * @var UserProfile $profile
         */
        $profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy([
            'email' => $appleSignInPayload->getEmail()
        ]);

        if (!is_null($profile)) {
            $userProfile = $profile->jsonSerialize();
        }

        $code = null;
        if ($auth && !is_null($profile)) {
            $oauth = new _oAuth2TokenController($this->auth, $this->entityManager);
            $code  = $oauth->authCode($request, $response, $appleSignInPayload->getEmail(), 'email', true);
        }

        $res = Http::status(200, [
            'email' => $appleSignInPayload->getEmail(),
            'user' => $appleSignInPayload->getUser(),
            'valid' =>  $appleSignInPayload->verifyUser($clientId),
            'user_profile' => $userProfile,
            'code' => $code
        ]);

        return $response->withJson($res, 200);
    }
}
