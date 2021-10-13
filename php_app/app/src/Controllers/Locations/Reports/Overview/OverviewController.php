<?php

namespace App\Controllers\Locations\Reports\Overview;

use App\Package\Organisations\OrganizationService;
use App\Package\RequestUser\UserProvider;
use App\Utils\Http;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use DateTime;
use DateInterval;
use Ramsey\Uuid\Uuid;

/**
 * Class OverviewController
 * @package App\Controllers\Locations\Reports\Overview
 */
final class OverviewController
{
	/**
	 * @var View $view
	 */
	private $view;

	/**
	 * @var UserProvider
	 */
	private $userProvider;

	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * OverviewController constructor.
	 * @param View $view
	 * @param UserProvider $provider
	 */
	public function __construct(
		View $view,
		UserProvider $provider,
		OrganizationService $organizationService
	) {
		$this->view         = $view;
		$this->userProvider = $provider;
		$this->organizationService = $organizationService;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception
	 */
	public function getOverview(Request $request, Response $response)
	{
		$now       = new DateTime('now');
		$orgId = $request->getAttribute('orgId', null);
		$orgUuId = Uuid::fromString($orgId);
		$startDate = (int) $request->getParam('startDate') ?? $now->getTimestamp();
		$endDate   = (int) $request->getParam('endDate') ?? $now->sub(new DateInterval('P30D'))->getTimestamp();
		$user   = $this
			->userProvider
			->getOauthUser($request);

		$organisation = $this->organizationService->getOrganisation($user, $orgUuId);

		$overview  = new Overview(
			(new DateTime())->setTimestamp($startDate),
			(new DateTime())->setTimestamp($endDate)
		);
		return $response->withJson(
			$this->view->addDataToOverview(
				$overview,
				$organisation->getAccessableSerials()
			)
		);
	}
}
