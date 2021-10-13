<?php

namespace App\Package\Reviews\Scrapers;

use App\Models\Integrations\Facebook\FacebookPages;
use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use App\Package\Reviews\Exceptions\ScrapeInvalidException;
use App\Package\Reviews\ReviewService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Facebook;

use Slim\Http\Request;
use Slim\Http\Response;

class FacebookScraper
{

	/**
	 * @var ReviewService $reviewService
	 */
	private $reviewService;

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var Facebook $facebookApi
	 *  private $facebookApi;
	 */

	public function __construct(EntityManager $entityManager, ReviewService $reviewService)
	{
		$this->facebookApi = new Facebook([
			'app_id'     => '526012921156845',
			'app_secret' => '47e6809319b4064b9ae305e2b5ac620c'
		]);
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
		if (!$settings->getFacebookPageId()) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'facebook page settings invalid');
		}

		/**
		 * @var FacebookPages $facebookPage
		 */
		$facebookPage = $this->entityManager->getRepository(FacebookPages::class)->findOneBy([
			'pageId'   => $settings->getFacebookPageId()
		]);

		if (!is_object($facebookPage)) {
			throw new ScrapeInvalidException($settings->getId()->toString(), 'facebook page not found');
		}
		try {
			$request = $this->facebookApi->get(
				'/' . $settings->getFacebookPageId() . '/ratings?fields=ratings,open_graph_story,reviewer',
				$facebookPage->getAccessToken()
			);
		} catch (FacebookResponseException $responseException) {
			$message = $responseException->getMessage();
			throw new ScrapeInvalidException($settings->getId()->toString(), "facebook api exception error ${message}");
		}

		$response = $request->getDecodedBody();
		return $response;
		/**
		 * @var UserReview[] $responseReviews
		 */
		$responseReviews = [];
		foreach ($response['data'] as $review) {
			$main = $review['open_graph_story'];
			if (empty($review['reviewer'])) {
				continue;
			}
			if (is_null($main['data']['review_text']) || empty($main['data']['review_text'])) {
				continue;
			}
			$hasUserLeftReview = $this->entityManager->getRepository(UserReview::class)->findOneBy([
				'reviewSettingsId'  => $settings->getId()->toString(),
				'platform' => 'facebook',
				'review' => $main['data']['review_text']
			]);
			if (is_object($hasUserLeftReview)) {
				continue;
			}
			$metadata = [
				'author_url' => 'https://facebook.com/' . $main['id'],
				'author_name' => $review['reviewer']['name'],
				'facebook_profile_id' => $review['reviewer']['id']
			];
			try {
				$userReview = $this->reviewService->createReview(
					$settings,
					$main['data']['review_text'],
					$main['data']['rating'] ?? 0,
					'facebook',
					$metadata,
					null
				);


				$userReview->setCreatedAtFromString($main['start_time']);
				$this->entityManager->persist($userReview);
				$responseReviews[] = $userReview;
			} catch (Exception $e) {
			}
		}
		$this->entityManager->flush();
		return $responseReviews;
	}
}
