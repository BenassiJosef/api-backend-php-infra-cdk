<?php

namespace App\Controllers\Auth;

use App\Models\OauthUser;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LegacyAuthController
{

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * Updates the new oauth_users table with a SHA1 password
     */

    public function put(Request $request, Response $response, $args)
    {

        $vars     = $request->getParsedBody();
        $uid      = $vars['uid'];
        $password = sha1($vars['password']);

        $this->em->createQueryBuilder()
            ->update(OauthUser::class, 'o')
            ->set('o.password', ':password')
            ->where('o.uid = :uid')
            ->setParameter('uid', $uid)
            ->setParameter('password', $password)
            ->getQuery()
            ->execute();

        $this->em->clear();

        return $response->write(
            json_encode('')
        );
    }
}
