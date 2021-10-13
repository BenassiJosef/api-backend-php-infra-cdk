<?php

namespace App\Package\Reviews\Controller;

use App\Package\Exceptions\BaseException;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Reviews\Reports\ReportRepository;
use App\Package\Reviews\ReviewSentiment;
use App\Package\Reviews\ReviewService;

class ReviewsReportController
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var OrganizationProvider $organizationProvider
	 */
	private $organizationProvider;

	/**
	 * @var ReviewService $reviewService
	 */
	private $reviewService;

	/**
	 * @var ReportRepository $reportRepository
	 */
	private $reportRepository;

	/**
	 * ProfileChecker constructor.
	 * @param EntityManager $entityManager
	 * @param OrganizationProvider $organizationProvider
	 */
	public function __construct(EntityManager $entityManager, OrganizationProvider $organizationProvider, ReviewService $reviewService)
	{
		$this->entityManager = $entityManager;
		$this->organizationProvider     = $organizationProvider;
		$this->reviewService = $reviewService;
		$this->reportRepository = new ReportRepository($this->entityManager);
	}

	public function getOverview(Request $request, Response $response): Response
	{
		$organizationId = $request->getAttribute('orgId');
		$pageId    = $request->getQueryParam('page_id', null);
		$summary = $this->reportRepository->getReviewSummary($organizationId, $pageId);

		return $response->withJson($summary);
	}


	public function getKeywords(Request $request, Response $response): Response
	{
		$reviewSentiment = new ReviewSentiment($this->entityManager);

		return $response->withJson($reviewSentiment->keywordsAndSentiment($request));
	}
}
