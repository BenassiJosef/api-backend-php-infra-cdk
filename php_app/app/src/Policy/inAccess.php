<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 29/12/2016
 * Time: 15:27
 */

namespace App\Policy;

use App\Package\Exceptions\BaseException;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\StatusCode;

class inAccess
{

	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;


	/**
	 * @var UserProvider $userProvider
	 */
	private $userProvider;

	public function __construct(EntityManager $em)
	{
		$this->userRoleChecker = new UserRoleChecker($em);
		$this->userProvider = new UserProvider($em);
	}

	public function __invoke($request, $response, $next)
	{

		//$user   = $request->getAttribute('user');
		$serial = $request->getAttribute('route')->getArgument('serial');

		$user = $this->userProvider->getOauthUser($request);
		$serials = $this->userRoleChecker->locationSerials($user);
		if (in_array($serial, $serials)) {
			$request = $request->withAttribute('serials', $serials);
			$request = $request->withAttribute('serial', $serial);
			//	$request = $request->withAttribute('user', $user->jsonSerialize());
			return $next($request, $response);
		}
		throw new BaseException('NO_ACCESS_TO_SERIAL', StatusCode::HTTP_FORBIDDEN);
	}
}
