<?php


namespace App\Package\Organisations;


use App\Package\RequestUser\UserProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class OrgTypeMiddleware
{
    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * OrgTypeMiddleware constructor.
     * @param UserRoleChecker $userRoleChecker
     * @param UserProvider $userProvider
     */
    public function __construct(UserRoleChecker $userRoleChecker, UserProvider $userProvider)
    {
        $this->userRoleChecker = $userRoleChecker;
        $this->userProvider    = $userProvider;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     * @throws Exception
     *
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $roles = $request->getAttribute(OrgTypeRoleConfigurationMiddleware::OrgTypeRoleKey, []);
        $orgTypes =  $request->getAttribute(OrgTypeRoleConfigurationMiddleware::OrgTypeKey, []);
        $user = $this->userProvider->getOauthUser($request);
        $canAccessOrgTypeAsRole = $this
            ->userRoleChecker
            ->hasAccessToOrganizationType(
                $user,
                $orgTypes,
                $roles
            );
        if ($canAccessOrgTypeAsRole) {
            return $next($request, $response);
        }
        return $response->withStatus(403)->write("User does not have access to this action");
    }
}