<?php

namespace App\Package\Reviews\Scrapers;

use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;

use App\Package\Reviews\Exceptions\ScrapeInvalidException;
use App\Package\Reviews\ReviewService;
use Curl\Curl;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;

class GoogleScraper
{
	/**
	 * @var string $baseEndPoint
	 */
	private $baseEndPoint = 'https://maps.googleapis.com/maps/api/place/details/json';

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
		$this->entityManager = $entityManager;
		$this->reviewService = $reviewService;
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
		if (!$settings->getGooglePlaceId()) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'google places id not found');
		}
		$request  = new Curl();
		$response = $request->get($this->baseEndPoint, [
			'placeid' => $settings->getGooglePlaceId(),
			'fields'  => 'review',
			'key'     => getenv('GOOGLE_MAPS_API_KEY')
		]);

		if ($request->error) {
			throw new ScrapeInvalidException($settings->getId()->toString(), $response->errorMessage);
		}

		if ($response->status !== 'OK') {
			throw new ScrapeInvalidException($settings->getId()->toString(), $response->error_message);
		}

		if (is_null($response->result->reviews)) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'no reviews found');
		}

		/**
		 * @var UserReview[] $responseReviews
		 */
		$responseReviews = [];

		foreach ($response->result->reviews as $review) {
			if (is_null($review->author_url)) {
				continue;
			}

			$reviewExistsAlready = $this->entityManager->getRepository(UserReview::class)->findOneBy([
				'reviewSettingsId'  => $settings->getId()->toString(),
				'platform' => 'google',
				'review' => $review->text
			]);

			if (is_object($reviewExistsAlready)) {
				continue;
			}
			try {
				$userReview = $this->reviewService->createReview($settings, $review->text, $review->rating, 'google', [
					'author_name' => $review->author_name,
					'author_url' => $review->author_url,
					'profile_photo_url' => $review->profile_photo_url
				], null);

				$userReview->setCreatedAtFromString($review->time);
				$this->entityManager->persist($userReview);
				$responseReviews[] = $userReview;
			} catch (Exception $e) {
			}
		}
		$this->entityManager->flush();
		return $responseReviews;
	}
}
