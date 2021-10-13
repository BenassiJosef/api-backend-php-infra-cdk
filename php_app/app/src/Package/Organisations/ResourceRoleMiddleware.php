<?php


namespace App\Package\Organisations;


use App\Models\OauthUser;
use App\Package\RequestUser\UserProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class ResourceRoleMiddleware
{
	/**
	 * @var UserProvider $userProvider
	 */
	private $userProvider;

	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;

	/**
	 * ResourceRoleMiddleware constructor.
	 * @param UserProvider $userProvider
	 * @param UserRoleChecker $userRoleChecker
	 */
	public function __construct(UserProvider $userProvider, UserRoleChecker $userRoleChecker)
	{
		$this->userProvider    = $userProvider;
		$this->userRoleChecker = $userRoleChecker;
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

		$serial = $request->getAttribute('route')->getArgument('serial');
		$orgId = $request->getAttribute('route')->getArgument('orgId');
		$resellerOrgId = $request->getAttribute('route')->getArgument('resellerOrgId');
		if ($serial === null && $orgId === null && $resellerOrgId === null) {
			return $response->withStatus(500, "route has no orgId/resellerOrgId/serial");
		}
		$user = $this->userProvider->getOauthUser($request);
		$allowableRoles = $request->getAttribute(ResourceRoleConfigurationMiddleware::AllowableRolesKey, []);
		if ($serial !== null && $this->userRoleChecker->hasAccessToLocationAsRole($user, $serial, $allowableRoles)) {
			$request = $request->withAttribute('serial', $serial);
			return $next($request, $response);
		}
		if ($orgId !== null && $this->userRoleChecker->hasAccessToOrganizationAsRole($user, $orgId, $allowableRoles)) {
			$request = $request->withAttribute('orgId', $orgId);
			return $next($request, $response);
		}
		if ($resellerOrgId !== null && $this->userRoleChecker->hasAccessToOrganizationAsRole($user, $resellerOrgId, $allowableRoles)) {
			$request = $request->withAttribute('resellerOrgId', $resellerOrgId);
			return $next($request, $response);
		}
		return $response->withStatus(403, "Forbidden");
	}
}
