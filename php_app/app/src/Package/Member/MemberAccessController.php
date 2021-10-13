<?php


namespace App\Package\Member;

use App\Package\Organisations\Locations\LocationRepositoryFactory;
use App\Package\Organisations\Locations\UserLocationAccessRepository;
use App\Package\Organisations\UserOrganizationAccessRepositoryFactory;
use App\Package\Pagination\RepositoryPaginatedResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class MemberAccessController
{
	/**
	 * @var UserOrganizationAccessRepositoryFactory $userAccessRepositoryFactory
	 */
	private $userAccessRepositoryFactory;

	/**
	 * @var LocationRepositoryFactory $userLocationRepositoryFactory
	 */
	private $userLocationRepositoryFactory;

	/**
	 * MemberAccessController constructor.
	 * @param UserOrganizationAccessRepositoryFactory $userAccessRepositoryFactory
	 */
	public function __construct(
		UserOrganizationAccessRepositoryFactory $userAccessRepositoryFactory,
		LocationRepositoryFactory $userLocationRepositoryFactory
	) {
		$this->userAccessRepositoryFactory = $userAccessRepositoryFactory;
		$this->userLocationRepositoryFactory = $userLocationRepositoryFactory;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function organizations(Request $request, Response $response): Response
	{
		return $response->withJson(
			RepositoryPaginatedResponse::fromRequestAndRepository(
				$request,
				$this->userAccessRepositoryFactory->paginatableRepository($request)
			)
		);
	}

	public function locations(Request $request, Response $response): Response
	{
		return $response->withJson(
			RepositoryPaginatedResponse::fromRequestAndRepository(
				$request,
				$this->userLocationRepositoryFactory->paginatableRepository($request)
			)
		);
	}
}
