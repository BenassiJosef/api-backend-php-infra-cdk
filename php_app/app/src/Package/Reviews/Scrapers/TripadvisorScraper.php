<?php

namespace App\Package\Reviews\Scrapers;

use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use App\Package\Reviews\Exceptions\ScrapeInvalidException;
use App\Package\Reviews\ReviewService;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Exception;

use Slim\Http\Request;
use Slim\Http\Response;

class TripadvisorScraper
{

	private $baseEndPoint = 'https://tripscraper.stampede.ai/2021';

	/**
	 * @var ReviewService $reviewService
	 */
	private $reviewService;

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	public function __construct(EntityManager $entityManager, ReviewService $reviewService)
	{
		$this->reviewService = $reviewService;
		$this->entityManager = $entityManager;
	}

	public function scrapeFromSnsRequest(Request $request, Response $response)
	{
		return $response->withJson($this->scrapeUsingId($request->getParsedBodyParam('setting_id', null)));
	}


	public function scrapeUsingId(string $id)
	{
		return $this->scrape($this->reviewService->getSettings($id));
	}

	/**
	 * @param ReviewSettings $settings
	 * @return UserReview[]
	 * @throws ScrapeInvalidException
	 */
	public function scrape(ReviewSettings $settings): array
	{

		if (!$settings->getIsActive()) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'page inactive');
		}
		if (!$settings->getTripadvisorUrl()) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'scrape settings invalid');
		}

		$request  = new Curl();
		$response = $request->get($this->baseEndPoint, [
			'url' => $settings->getTripadvisorUrl()
		]);

		if ($response->status === 400) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'scrape server returned error code 400');
		}

		if (empty($response->reviews)) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'no reviews found');
		}
		/**
		 * @var UserReview[] $responseReviews
		 */
		$responseReviews = [];
		foreach ($response->reviews as $review) {
			$reviewExistsAlready = $this->entityManager->getRepository(UserReview::class)->findOneBy([
				'reviewSettingsId'  => $settings->getId()->toString(),
				'platform' => 'tripadvisor',
				'review' => $review->review
			]);

			if (is_object($reviewExistsAlready)) {
				continue;
			}
			try {
				$userReview =  $this->reviewService->createReview($settings, $review->review, $review->rating, 'tripadvisor', [
					'author_name' => $review->author_name,
					'author_url' => $review->author_url,
					'profile_photo_url' => $review->profile_photo_url,
					'title' => $review->title
				], null);

				$responseReviews[] = $userReview;
			} catch (Exception $e) {
			}
		}



		return $responseReviews;
	}
}
