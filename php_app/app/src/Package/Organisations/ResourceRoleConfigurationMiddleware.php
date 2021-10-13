<?php


namespace App\Package\Organisations;


use Slim\Http\Request;
use Slim\Http\Response;

class ResourceRoleConfigurationMiddleware
{
	const AllowableRolesKey = "ResourceRoleAllowableRoles";

	/**
	 * @var int[] $allowableRoles
	 */
	private $allowableRoles = [];

	/**
	 * ResourceRoleConfigurationMiddleware constructor.
	 * @param int[] $allowableRoles
	 */
	public function __construct(array $allowableRoles)
	{
		$this->allowableRoles = $allowableRoles;
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

		return $next(
			$request->withAttribute(
				self::AllowableRolesKey,
				$this->allowableRoles
			),
			$response
		);
	}
}
