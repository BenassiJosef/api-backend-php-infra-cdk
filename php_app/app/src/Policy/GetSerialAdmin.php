<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 28/02/2017
 * Time: 09:46
 */


namespace App\Policy;

use App\Models\Locations\LocationSettings;
use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Role;

/**
 * @package App\Policy
 */
class GetSerialAdmin
{
	/**
	 * @var LocationService
	 */
	private $locationService;

	/**
	 * isSerialAdmin constructor.
	 * @param LocationService $locationService
	 */
	public function __construct(LocationService $locationService)
	{
		$this->locationService = $locationService;
	}

	public function __invoke(Request $request, Response $response, $next)
	{
		$serial = $request->getAttribute('route')->getArgument('serial');
		if ($serial === 'all' || $serial === 'null') {
			return $next($request, $response);
		}
		$locationSetting = $this->locationService->getLocationBySerial($serial);
		if (is_null($locationSetting)) {
			return $response->withStatus(404)->withJson(Http::status(404, 'NO_NETWORK_ADMIN'));
		}
		$organization = $locationSetting->getOrganization();
		if (is_null($organization)) {
			return $response->withStatus(404)->withJson(Http::status(404, 'NO_NETWORK_ADMIN'));
		}
		$owner   = $organization->getOwner();

		$request = $request
			->withAttribute('accessUser', $owner->getArrayCopy())
			->withAttribute('locationOrganisation', $organization);
		return $next($request, $response);
	}
}
