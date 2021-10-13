<?php

declare(strict_types=1);
/**
 * Created by Chris Greening on 04/02/2020 at 16:41
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Organizations;


use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Package\Exceptions\BaseException;
use App\Package\Member\MemberService;
use App\Package\Member\UserCreationInput;
use App\Package\Organisations\Exceptions\OrganizationIdMissingException;
use App\Package\Organisations\Exceptions\OrganizationNotFoundException;
use App\Package\Organisations\LocationAccessChangeRequestProvider;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganisationNotFoundException;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\OrganisationAccessDeniedException;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Monolog\Logger;
use Nette\NotImplementedException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Throwable;

class OrganizationsController
{
	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var UserProvider
	 */
	private $userProvider;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var LocationService $locationService
	 */
	private $locationService;

	/**
	 * @var MemberService $memberService
	 */
	private $memberService;

	/**
	 * @var CacheEngine $cache
	 */
	private $cache;

	/**
	 * OrganizationsController constructor.
	 * @param Logger $logger
	 * @param OrganizationService $organizationService
	 * @param UserProvider $userProvider
	 * @param EntityManager $em
	 * @param LocationService $locationService
	 * @param MemberService $memberService
	 */
	public function __construct(
		Logger $logger,
		OrganizationService $organizationService,
		UserProvider $userProvider,
		EntityManager $em,
		LocationService $locationService,
		MemberService $memberService
	) {

		$this->organizationService = $organizationService;
		$this->logger              = $logger;
		$this->userProvider        = $userProvider;
		$this->em                  = $em;
		$this->locationService     = $locationService;
		$this->memberService       = $memberService;
		$key = getenv('CONNECT_REDIS');
		if ($key) {
			$this->cache  = new CacheEngine($key);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 */
	public function updateOrganisationRoute(Request $request, Response $response): Response
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			throw new OrganizationIdMissingException();
		}

		$name = $request->getParsedBodyParam('name', $default = null);

		if (is_null($name)) {
			return $response->withJson(Http::status(400, "Missing name in payload"), 400);
		}

		try {
			$org = $this->organizationService->updateName($this->userProvider->getOauthUser($request), $orgId, $name);
			if (is_null($org)) {
				return $response->withJson(Http::status(404, "Organisation not found"), 404);
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $org->jsonSerialize()));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function getOrganisationRoute(Request $request, Response $response): Response
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}

		$orgUuId = Uuid::fromString($orgId);

		try {
			$org = $this->organizationService->getOrganisation(
				$this->userProvider->getOauthUser($request),
				$orgUuId
			);
			if (is_null($org)) {
				return $response->withJson(Http::status(404, "Organisation not found"), 404);
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $org->jsonSerialize()));
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function setParentRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}

		$orgUuId = Uuid::fromString($orgId);

		$newParent = $request->getParsedBodyParam('parentId', $default = null);
		if (is_null($newParent)) {
			return $response->withJson(Http::status(400, "Missing parentId in payload"), 400);
		}
		$parentUuid = UUid::fromString($newParent);

		try {
			$org = $this->organizationService->updateParent($this->userProvider->getOauthUser($request), $orgUuId, $parentUuid);
			if (is_null($org)) {
				return $response->withJson(Http::status(404, "Organisation not found"), 404);
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $org->jsonSerialize()));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function getChildrenRoute(Request $request, Response $response): Response
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);

		try {
			/** @var Organization[] $children */
			$children = $this->organizationService->getChildren($this->userProvider->getOauthUser($request), $orgUuId);
			$results  = [];
			foreach ($children as $child) {
				$results[] = $child->jsonSerialize();
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $results));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 */
	public function setChildrenRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);

		/** @var UuidInterface[] $childIds */
		$childIds       = [];
		$childIdStrings = $request->getParsedBodyParam("childIds", []);
		foreach ($childIdStrings as $childIdString) {
			$childIds[] = UUid::fromString($childIdString);
		}
		try {
			$org = $this->organizationService->setChildren($this->userProvider->getOauthUser($request), $orgUuId, $childIds);
			$this->em->flush();

			return $response->withJson(Http::status(200, $org));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function addLocationRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);
		$serial  = $request->getParsedBodyParam("serial", []);
		try {
			$org = $this->organizationService->addLocation($this->userProvider->getOauthUser($request), $orgUuId, $serial);
			$this->em->flush();

			return $response->withJson(Http::status(200, $org));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function setLocationsRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);
		$serials = $request->getParsedBodyParam("serials", []);
		try {
			$org = $this->organizationService->addLocations($this->userProvider->getOauthUser($request), $orgUuId, $serials);
			$this->em->flush();

			return $response->withJson(Http::status(200, $org));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function getSingleLocationRoute(Request $request, Response $response)
	{
		$orgId  = $request->getAttribute("orgId");
		$serial = $request->getAttribute("serial");

		if (empty($orgId) || empty($serial)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$loc = $this->locationService->getLocationBySerial($serial);
		if (is_null($loc)) {
			return $response->withJson(Http::status(404, "Not Found"), 404);
		}

		return $response->withJson(Http::status(200, $loc));
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function getLocationsRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);
		try {
			$locations = $this->organizationService->getLocations(
				$this->userProvider->getOauthUser($request),
				$orgUuId
			);
			$results   = [];
			foreach ($locations as $location) {
				$results[] = $location->jsonSerialize();
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $results));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function getUsersRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$orgUuId = Uuid::fromString($orgId);

		try {
			$users   = $this->organizationService->getUsers($this->userProvider->getOauthUser($request), $orgUuId);
			$results = [];
			foreach ($users as $user) {
				$results[] = $user->jsonSerialize();
			}
			$this->em->flush();

			return $response->withJson(Http::status(200, $results));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function getAllUsersRoute(Request $request, Response $response)
	{
		/** @var OrganizationAccess $users */
		$users   = $this->organizationService->getAllUsers($this->userProvider->getOauthUser($request));
		$results = [];
		foreach ($users as $user) {
			$results[] = $user->jsonSerialize();
		}
		$this->em->flush();

		return $response->withJson(Http::status(200, $results));
	}

	public function getLocationUserRoute(Request $request, Response $response)
	{
		$orgId =  $request->getAttribute("orgId");
		$orgUuId = Uuid::fromString($orgId);



		$uid   = $request->getQueryParam("uid", null);
		$user = $this
			->memberService
			->getUserByIdString($uid);

		if (is_null($user)) {
			throw new BaseException('USER_NOT_FOUND', StatusCode::HTTP_NOT_FOUND);
		}

		$org = $this->organizationService
			->getOrganisation($user, $orgUuId);

		if (is_null($org)) {
			throw new BaseException('ORGANISATION_MISSING', StatusCode::HTTP_NOT_FOUND);
		}

		$access = $this->locationService->getCurrentLocationOrganisationAccess($user, $org);
		$user->setOrganisationAccess($access);

		return $response->withJson($user);
	}

	public function updateLocationUserRoute(Request $request, Response $response)
	{
		$orgId =  $request->getAttribute("orgId");
		$orgUuId = Uuid::fromString($orgId);
		try {
			$uid   = $request->getParsedBodyParam("uid", null);
			$user = $this->memberService->getUserByIdString($uid);
			$locationProvider = new LocationAccessChangeRequestProvider($this->em);
			$provider = $locationProvider->make($request, $user);

			$org = $this->organizationService->getOrganisation($user, $orgUuId);

			$newUser = $this->locationService->updateLocations($provider, $org);
			if ($this->cache) {
				$this->cache->delete($uid . ':profile');
			}

			return $response->withJson($newUser);
		} catch (Throwable $e) {

			$response->withStatus(403)->withJson($e->getMessage());
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function addUserRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}

		$uid   = $request->getParsedBodyParam("uid", null);
		$email = $request->getParsedBodyParam("email", null);
		$role  = $request->getParsedBodyParam("role", null);


		if (is_null($role)) {
			return $response->withJson(Http::status(400, "Missing role id"), 400);
		}

		$orgUuId = Uuid::fromString($orgId);
		try {
			$user = null;
			if ($uid !== null) {
				$user = $this
					->memberService
					->getUserByIdString($uid);
			}
			if ($email !== null) {
				$user = $this
					->memberService
					->getUserByEmail($email);
			}
			$input = null;
			if ($user === null) {
				$input = UserCreationInput::createFromArray($request->getParsedBody());
			}
			if ($user === null && !is_null($input->getFirst()) && !is_null($input->getLast()) && !is_null($input->getCompany()) && !is_null($input->getPassword())) {
				$user = $this
					->memberService
					->createUser($input);
			}
			if ($user === null) {
				return $response->withJson(Http::status(404, "Could not find or add user"), 404);
			}
			$addedUser = $this->organizationService
				->addUser(
					$this->userProvider->getOauthUser($request),
					$orgUuId,
					$role,
					$user
				);
			$this->em->flush();

			return $response->withJson(Http::status(200, $addedUser->jsonSerialize()));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function removeUserRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");

		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}

		$uid = $request->getAttribute("uid");
		if (is_null($uid)) {
			return $response->withJson(Http::status(400, "Missing user id"), 400);
		}

		$orgUuId = Uuid::fromString($orgId);
		try {
			$org = $this->organizationService->removeUser($this->userProvider->getOauthUser($request), $orgUuId, $uid);
			$this->em->flush();

			return $response->withJson(Http::status(200, $org->jsonSerialize()));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function addNewChildRoute(Request $request, Response $response)
	{
		$orgId = $request->getAttribute("orgId");
		if (is_null($orgId)) {
			return $response->withJson(Http::status(400, "Missing organisation id"), 400);
		}
		$name = $request->getParsedBodyParam("name");
		if (is_null($name)) {
			return $response->withJson(Http::status(400, "Missing name in body"), 400);
		}

		$orgUuId = Uuid::fromString($orgId);
		try {
			$newChild = $this->organizationService->createChild($this->userProvider->getOauthUser($request), $orgUuId, $name);
			$this->em->flush();

			return $response->withJson(Http::status(200, $newChild->jsonSerialize()));
		} catch (OrganisationAccessDeniedException $ex) {
			return $response->withJson(Http::status(403, $ex->getMessage()), 403);
		} catch (OrganisationNotFoundException $ex) {
			return $response->withJson(Http::status(404, $ex->getMessage()), 404);
		}
	}
}
