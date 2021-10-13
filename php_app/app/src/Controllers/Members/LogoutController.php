<?php

/**
 * Created by jamieaitken on 12/03/2018 at 17:15
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Members;


use App\Models\OauthAccessTokens;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LogoutController
{

    protected $connectCache;
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
        $this->em           = $em;
    }

    public function logoutRoute(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        $http  = $request->getServerParam('HTTP_AUTHORIZATION');
        $token = substr($http, 7);
        $send  = $this->logout($user, $token);

        return $response->withJson($send, $send['status']);
    }

    public function logout(array $user, string $token)
    {
        $this->em->createQueryBuilder()
            ->delete(OauthAccessTokens::class, 'u')
            ->where('u.accessToken = :token')
            ->andWhere('u.userId = :userId')
            ->setParameter('token', $token)
            ->setParameter('userId', $user['uid'])
            ->getQuery()
            ->execute();

        $this->connectCache->deleteMultiple([
            $user['uid'] . ':location:accessibleLocations',
            $user['uid'] . ':profile'
        ]);

        return Http::status(200);
    }
}
