<?php

namespace App\Package\Reviews;

use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use App\Models\Reviews\UserReviewKeywords;
use App\Package\Reviews\Exceptions\UserReviewNotFoundException;
use Doctrine\ORM\EntityManager;


use Aws\Comprehend\ComprehendClient;
use Slim\Http\Request;

use function Rap2hpoutre\RemoveStopWords\remove_stop_words;

class ReviewSentiment
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;


	/**
	 * @var ComprehendClient $client
	 */
	protected $client;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;

		$this->client = new ComprehendClient([
			'region' => 'eu-west-1',
			'version' => '2017-11-27'
		]);
	}

	public function createSentimentUsingReview(UserReview $userReview): UserReview
	{
		if (empty($userReview->getReview()) || is_null($userReview->getReview())) {
			return  $userReview;
		}

		$keyPhrases = $this->client->detectKeyPhrases([
			'LanguageCode' => 'en',
			'Text' => $userReview->getReview()
		])->get('KeyPhrases');

		$sentiment = $this->client->detectSentiment([
			'LanguageCode' => 'en',
			'Text' => $userReview->getReview()
		])->toArray();

		$userReview->setSentimentFromAwsComprehend($sentiment);

		foreach ($keyPhrases as $phrase) {
			$reviewKeyPhrase = new UserReviewKeywords(
				$userReview,
				$this->formatString($phrase['Text']),
				$phrase['Score']
			);
			$this->entityManager->persist($reviewKeyPhrase);
		}
		$this->entityManager->flush();

		return  $userReview;
	}

	public function keywordsAndSentiment(Request $request)
	{
		$organizationId = $request->getAttribute('orgId', null);
		$pageId = $request->getQueryParam('page_id', null);
		$qb = $this->entityManager->createQueryBuilder();
		$expr = $qb->expr();
		$query = $qb
			->select('rc.sentiment, rk.text, COUNT(rk.text) as occurrences, rk.score')
			->from(UserReview::class, 'rc')
			->leftJoin(UserReviewKeywords::class, 'rk', 'WITH', 'rc.id = rk.userReviewId')
			->leftJoin(ReviewSettings::class, 'rs', 'WITH', 'rs.id = rc.reviewSettingsId')
			->where('rc.organizationId = :orgId')
			->andWhere('rs.deletedAt IS NULL')
			->andWhere('rc.sentiment IS NOT NULL')
			->setParameter('orgId', $organizationId);

		if (!is_null($pageId)) {
			$query = $query
				->andWhere($expr->eq('rs.id', ':pageId'))
				->setParameter('pageId', $pageId);
		}

		$query =  $query
			->groupBy('rc.sentiment, rk.text')
			->having('COUNT(rk.text) > 1')
			->getQuery()
			->getArrayResult();

		foreach ($query as $key => $keyword) {
			foreach ($keyword as $wordKey => $word) {
				if (is_numeric($word)) {
					$query[$key][$wordKey] = (float) $word;
				}
			}
		}

		return  $query;
	}


	public function sentimentForReview(string $userReviewId): UserReview
	{
		/**
		 * @var UserReview $userReview
		 */
		$userReview =  $this->entityManager->getRepository(UserReview::class)->find($userReviewId);

		if (is_null($userReview)) {
			throw new UserReviewNotFoundException($userReviewId);
		}
		return  $this->createSentimentUsingReview($userReview);
	}

	public function formatString(string $text): string
	{
		$text = remove_stop_words($text);
		return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
	}
}
