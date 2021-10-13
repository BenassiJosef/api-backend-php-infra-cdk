<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 19/01/2017
 * Time: 23:56
 */

namespace App\DataAccess;

use Slim\Http\Request;
use Slim\Http\Response;


class _MemberAccess
{

    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * PASS ADMIN IS AND EITHER GET A RESPONSE OR A OAUTH USER
     * @param Request $request
     * @param Response $response
     * @param string $uid
     * @return mixed
     */

    public function hasAdminAccess(Request $request, Response $response, $uid = '')
    {

        $user = $request->getAttribute('user');

        $currentRole = $user['role'];

        $accessUserParams = [
            'uid' => $uid
        ];

        if ($currentRole === 1) {
            $queryParams['reseller'] = $user['uid'];
        } else if ($currentRole === 2) {
            if ($user['uid'] !== $uid) {
                return $response->withStatus(403)->write(
                    json_encode([
                        'code' => 403,
                        'reason' => 'INVALID_PERMISSION_ROLE'
                    ])
                );
            }
        } else if ($currentRole >= 3) {
            $queryParams['uid'] = $user['uid'];
            $queryParams['admin'] = $uid;
        }

        $accessUser = $this->em->getRepository('App\Models\OauthUser')->findOneBy($accessUserParams);

        if ($accessUser) {
            return $accessUser->getArrayCopy();
        }

        return $response->withStatus(403)->write(
            json_encode([
                'code' => 403,
                'reason' => 'INVALID_PERMISSION_ROLE'
            ])
        );

    }

}