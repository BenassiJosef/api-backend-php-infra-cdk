<?php

/**
 * Created by jamieaitken on 04/05/2018 at 10:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Policy;


use App\Controllers\Auth\_oAuth2Controller;
use App\Models\User\UserAccount;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NearlyLogInService
{

    protected $auth;
    protected $em;

    public function __construct(_oAuth2Controller $auth, EntityManager $em)
    {
        $this->auth = $auth;
        $this->em   = $em;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $token     = $this->auth->validateToken($request);
        $arguments = $request->getAttribute('route')->getArguments();

        if (!$token) {
            return $response->withStatus(403);
        }

        if ($arguments['id'] !== 'me') {
            return $response->withStatus(403);
        }

        $request = $request->withAttribute('profileId', intval($this->auth->getUid()));
        $request = $request->withAttribute('nearlyUser', [
            'valid'     => true,
            'profileId' => intval($this->auth->getUid())
        ]);

        return $next($request, $response);
    }
}
