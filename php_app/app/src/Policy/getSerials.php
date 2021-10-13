<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 02/06/2017
 * Time: 13:19
 */

namespace App\Policy;


use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\OauthUser;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class getSerials
{
    /**
     * @var UserRoleChecker
     */
    private $userRoleChecker;
    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * getSerials constructor.
     * @param UserRoleChecker $userRoleChecker
     * @param UserProvider $userProvider
     */
    public function __construct(UserRoleChecker $userRoleChecker, UserProvider $userProvider)
    {
        $this->userRoleChecker = $userRoleChecker;
        $this->userProvider = $userProvider;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $locations = $this->userRoleChecker->locationSerials($this->userProvider->getOauthUser($request));
        $auser = $request->getAttribute('accessUser');
        $auser['access'] = $locations;

        $request = $request->withAttribute('accessUser', $auser);

        return $next($request, $response);
    }
}