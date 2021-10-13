<?php

namespace App\Package\Reviews;

use App\Controllers\Integrations\Mail\MailSender;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Billing\Organisation\Subscriptions;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Reviews\ReviewSettings;
use App\Models\Reviews\UserReview;
use App\Models\UserProfile;

use App\Package\Exceptions\InvalidUUIDException;
use App\Package\Organisations\Exceptions\OrganizationNotFoundException;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Reports\FromQuery;
use App\Package\Reviews\Exceptions\DuplicateUserReviewException;
use App\Package\Reviews\Exceptions\ReviewSettingsNotFoundException;
use App\Package\Reviews\Exceptions\UserReviewNotFoundException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Doctrine\ORM\Query\Expr\OrderBy;
use DOMDocument;
use DOMXPath;
use Ramsey\Uuid\UuidInterface;
use StampedeTests\app\src\Package\Exceptions\NotFoundException;

class ReviewService
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var QueueSender $queue
	 */
	private $queue;

	/**
	 * @var MailSender $mailSender
	 */
	private $mailSender;

	/**
	 * @var OrganizationProvider $organizationProvider
	 */
	private $organizationProvider;

	public function __construct(EntityManager $entityManager, MailSender $mailSender)
	{
		$this->entityManager        = $entityManager;
		$this->mailSender           = $mailSender;
		$this->queue                = new QueueSender();
		$this->organizationProvider = new OrganizationProvider($entityManager);
	}

	public function orgHasReviews(string $orgId): bool
	{

		/**
		 * @var Subscriptions $billing
		 */
		$billing = $this->entityManager
			->getRepository(Subscriptions::class)
			->findOneBy(
				[
					'organizationId' => $orgId
				]
			);

		if (is_null($billing)) {
			return false;
		}

		return $billing->hasAddon(Subscriptions::ADDON_REVIEWS);
	}

	public function getSettingsFromRequest(Request $request): ReviewSettings
	{
		return $this->getSettings($request->getAttribute('id'));
	}

	public function getReviewFromSerial(string $serial): ?ReviewSettings
	{
		return $this->entityManager->getRepository(ReviewSettings::class)->findOneBy(
			[
				'serial' => $serial,
				'isActive'       => true,
			]
		);
	}

	public function getReviewFromId(string $id): UserReview
	{
		/**
		 * @var UserReview $userReview
		 */
		$userReview = $this->entityManager->getRepository(UserReview::class)->find($id);

		if (is_null($userReview)) {
			throw new UserReviewNotFoundException($id);
		}

		return $userReview;
	}

	/**
	 * @param Request $request
	 * @return ReviewSettings
	 * @throws NotFoundException
	 * @throws InvalidUUIDException
	 * @throws OrganizationNotFoundException
	 */
	public function getOrganizationReviewSettingsFromRequest(Request $request): ReviewSettings
	{
		$organization = $this
			->organizationProvider
			->organizationForRequest($request);

		/** @var ReviewSettings | null $reviewSettings */
		$reviewSettings = $this
			->entityManager
			->getRepository(ReviewSettings::class)
			->findOneBy(
				[
					'organizationId' => $organization->getId(),
					'id'             => $request->getAttribute('id'),
					'deletedAt'      => null,
					'isActive'       => true,
				]
			);
		if ($reviewSettings === null) {
			throw new NotFoundException();
		}
		return $reviewSettings;
	}

	public function getSettings(string $settingsId): ReviewSettings
	{
		/**
		 * @var ReviewSettings $settings
		 */
		$settings = $this
			->entityManager
			->getRepository(ReviewSettings::class)
			->find($settingsId);

		if (is_null($settings)) {
			throw new ReviewSettingsNotFoundException($settingsId);
		}

		return $settings;
	}

	public function canReview(ReviewSettings $settings, int $profileId = null, bool $ignoreNearly = true): bool
	{
		if (!$settings->getIsActive()) {
			return false;
		}
		if (!$settings->hasValidSubscription()) {
			return false;
		}
		if (is_null($profileId)) {
			return true;
		}


		$qb         = $this->entityManager->createQueryBuilder();
		$expr       = $qb->expr();
		$findReview = $qb->select('r')
			->from(UserReview::class, 'r')
			->where($expr->eq('r.reviewSettingsId', ':settingsId'))
			->setParameter('settingsId', $settings->getId()->toString())
			->andWhere($expr->eq('r.profileId', ':profileId'))
			->setParameter('profileId', $profileId);

		if ($ignoreNearly) {
			$findReview = $findReview->andWhere($expr->neq('r.platform', ':platform'))
				->setParameter('platform', 'nearly');
		}

		/** @var UserReview | null $findReview */
		$findReview = $findReview->orderBy('r.createdAt', 'DESC')
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();

		if (!$findReview) {
			return true;
		}

		return $findReview->canReviewAtThisTime();
	}




	public function createReview(
		ReviewSettings $settings,
		string $review,
		int $rating,
		string $platform,
		?array $metadata,
		?UserProfile $profile
	): UserReview {

		if (!is_null($profile) && !$this->canReview($settings, $profile->getId())) {
			throw new DuplicateUserReviewException($settings->getId()->toString());
		}

		$userReview = new UserReview(
			$settings,
			$review,
			$rating,
			$platform,
			$metadata,
			$this->organizationProvider->getOrganizationRegistration(
				$settings->getOrganization(),
				$profile
			)
		);

		$this->entityManager->persist($userReview);
		$this->entityManager->flush();

		$this->queue->sendMessage(
			[
				'notificationContent' => [
					'objectId' => $userReview->getId()->toString(),
					'title'    => 'Review Received',
					'kind'     => 'review_received',
					'link'     => '/reviews/responses',
					'serial'   => $settings->getSerial(),
					'orgId'    => $settings->getOrganizationId(),
					'reviewId' => $userReview->getId()->toString(),
					'message'  => $userReview->getReview()
				],
				'data'                => $userReview->jsonSerialize()
			],
			QueueUrls::NOTIFICATION
		);


		return $userReview;
	}

	public function getReviews(Request $request): PaginatedResponse
	{

		$rating    = $request->getQueryParam('rating', null);
		$review    = $request->getQueryParam('review', null);
		$pageId    = $request->getQueryParam('page_id', null);
		$platform  = $request->getQueryParam('platform', null);
		$sentiment = $request->getQueryParam('sentiment', null);
		$done      = $request->getQueryParam('done', null);
		$organization = $this->organizationProvider->organizationForRequest($request);
		$sort      = strtoupper($request->getQueryParam('sort', 'DESC'));
		$params    = new FromQuery($request);

		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr         = $queryBuilder->expr();

		$query = $queryBuilder
			->select('r')
			->from(UserReview::class, 'r')
			->leftJoin(ReviewSettings::class, 'rs', 'WITH', 'rs.id = r.reviewSettingsId')
			->where($expr->eq('r.organizationId', ':organizationId'))
			->andWhere('rs.deletedAt IS NULL')
			->setParameter('organizationId', $params->getOrganizationId());

		if (!is_null($params->getSerial())) {
			$query = $query
				->andWhere($expr->eq('rs.serial', ':serial'))
				->setParameter('serial', $params->getSerial());
		}

		if ($organization->getIsRestrictedByLocation()) {
			$query = $query
				->andWhere($expr->in('rs.serial', ':serials'))
				->setParameter('serials', $organization->getAccessableSerials());
		}

		if (!is_null($rating)) {
			$query = $query
				->andWhere($expr->eq('r.rating', ':rating'))
				->setParameter('rating', (int)$rating);
		}

		if (!is_null($sentiment)) {
			$query = $query
				->andWhere($expr->eq('r.sentiment', ':sentiment'))
				->setParameter('sentiment', $sentiment);
		}


		if (!is_null($done)) {

			if ($done === 'true') {
				$query = $query
					->andWhere($expr->isNotNull('r.doneAt'));
			} else {
				$query = $query
					->andWhere($expr->isNull('r.doneAt'));
			}
		}

		if (!is_null($pageId)) {
			$query = $query
				->andWhere($expr->eq('r.reviewSettingsId', ':pageId'))
				->setParameter('pageId', $pageId);
		}

		if (!is_null($platform)) {
			$query = $query
				->andWhere($expr->eq('r.platform', ':platform'))
				->setParameter('platform', $platform);
		}

		if (!is_null($review)) {
			$query = $query
				->andWhere($expr->like('LOWER(r.review)', $expr->literal('%' . strtolower($review) . '%')));
		}

		$query = $query
			->orderBy(new OrderBy('r.createdAt', $sort))
			->setMaxResults($params->getLimit())
			->setFirstResult($params->getOffset())
			->getQuery();

		return new PaginatedResponse($query);
	}

	public function updateReview(Request $request): UserReview
	{
		$reviewId = $request->getAttribute('review_id');
		$done     = $request->getParsedBodyParam('done', false);
		$review   = $this->getReviewFromId($reviewId);
		$review->setDone($done);
		$this->entityManager->flush();
		return $review;
	}

	public function sendEmail(ReviewSettings $settings, UserProfile $profile)
	{
		$this
			->mailSender
			->send(
				$profile->emailSendTo(),
				array_merge($settings->emailArray(), ['profile_id' => $profile->getId()]),
				"ReviewTemplate",
				$settings->getSubject()
			);
		return $settings->emailArray();
	}



	public function migrate()
	{

		$insertStatement = "
INSERT INTO organization_review_settings 
(
id, 
organization_id, 
template_id, 
created_at, 
edited_at, 
subject, 
send_after_secs, 
header_image, 
background_image,
title_text,
text_alignment,
is_active,
serial,
facebook_page_id,
google_page_id,
tripadvisor_url,
body_text
) VALUES (
UUID(), 
:organizationId, 
:templateId,
:createdAt,
:editedAt,
:subject,
:sendAfterSecs,
:headerImage,
:backgroundImage,
:titleText,
:textAlignment,
:isActive,
:serial,
:facebookPageId,
:googlePageId,
:tripadvisorUrl,
:bodyText
) 
";

		$migrateQuery = "SELECT 
mc.name, 
mc.created  as created_at,
mc.templateId as template_id,
mcm.emailContents,
mc.active as is_active,
mc.edited as edited_at,
mc.organization_id,
mcm.subject,
substr(mc.name,instr(mc.name,'_') + 1) as serial,
nsl.googlePlaceId as google_page_id,
nsl.tripadvisorId as tripadvisor_url,
nss.page as facebook_page_id,
mceo.value * 60 as send_after_secs
  FROM 
marketing_campaigns mc 
LEFT JOIN marketing_campaign_messages mcm ON mcm.id = mc.messageId 
LEFT JOIN marketing_campaign_events mce ON mce.id = mc.eventId 
LEFT JOIN marketing_campaign_events_options mceo ON mceo.eventId = mce.id 
LEFT JOIN location_settings ls ON ls.serial = substr(mc.name,instr(mc.name,'_') + 1)
LEFT JOIN organization_review_settings rs ON rs.serial = ls.serial
LEFT JOIN network_settings_location nsl ON nsl.id = ls.location
LEFT JOIN network_settings_social nss ON nss.id = ls.facebook
WHERE 
mc.organization_id IS NOT NULL 
AND
mcm.emailContents IS NOT NULL 

AND
rs.serial IS NULL
AND
mc.name LIKE 'REVIEW_%'
GROUP BY mc.name LIMIT 0,100";


		$conn  = $this->entityManager->getConnection();
		$query = $conn->prepare($migrateQuery);

		$query->execute();
		$results = $query->fetchAll();

		$statement = $this
			->entityManager
			->getConnection()
			->prepare($insertStatement);

		$skipped = [];
		foreach ($results as $key => $result) {


			$dom = new DOMDocument();
			$dom->loadHTML($result['emailContents']);
			$xpath        = new DOMXPath($dom);
			$messageTitle = $xpath->query("//td[contains(@class, 'message-title')]");
			if (count($messageTitle) === 0) {
				$skipped[] = $result;
				continue;
			}
			$results[$key]['text_alignment'] = $xpath->query("//td[contains(@class, 'message-formatting-header')]")->item(0)->getAttribute('align');
			$results[$key]['title_text']     = trim(preg_replace('/\s+/', ' ', $messageTitle[0]->nodeValue));

			$bodyText                      = $xpath->query("//td[contains(@class, 'message-formatting-body')]");
			$results[$key]['body_text']    = $bodyText[0]->nodeValue;
			$results[$key]['header_image'] = $xpath->query("//img")->item(0)->getAttribute('src');

			// $results[$key]['background_image'] = $bg->nodeValue;
			$results[$key]['background_image'] = null;
			$pattern                           = '/background-image:\s*url\(\s*([\'"]*)(?P<file>[^\1]+)\1\s*\)/i';
			$matches                           = array();
			if (preg_match($pattern, $result['emailContents'], $matches)) {
				$results[$key]['background_image'] = str_replace('"', '', htmlspecialchars_decode($matches['file']));
			}

			$statement->bindValue(':googlePageId', $results[$key]['google_page_id'], ParameterType::STRING);
			$statement->bindValue(':tripadvisorUrl', $results[$key]['tripadvisor_url'], ParameterType::STRING);
			$statement->bindValue(':facebookPageId', $results[$key]['facebook_page_id'], ParameterType::STRING);
			$statement->bindValue(':serial', $results[$key]['serial'], ParameterType::STRING);
			$statement->bindValue(':createdAt', $results[$key]['created_at'], ParameterType::STRING);
			$statement->bindValue(':editedAt', $results[$key]['edited_at'], ParameterType::STRING);
			$statement->bindValue(':isActive', $results[$key]['is_active'], ParameterType::INTEGER);
			$statement->bindValue(':sendAfterSecs', $results[$key]['send_after_secs'] ?? 86400, ParameterType::INTEGER);
			$statement->bindValue(':organizationId', $results[$key]['organization_id'], ParameterType::STRING);
			$statement->bindValue(':templateId', $results[$key]['template_id'], ParameterType::STRING);
			$statement->bindValue(':subject', $results[$key]['subject'], ParameterType::STRING);
			$statement->bindValue(':backgroundImage', $results[$key]['background_image'], ParameterType::STRING);

			$statement->bindValue(':headerImage', $results[$key]['header_image'], ParameterType::STRING);
			$statement->bindValue(':bodyText', $results[$key]['body_text'], ParameterType::STRING);
			$statement->bindValue(':titleText', $results[$key]['title_text'], ParameterType::STRING);
			$statement->bindValue(':textAlignment', $results[$key]['text_alignment'], ParameterType::STRING);
			$statement->execute();
		}

		return ['results' => $results, 'skipped' => $skipped];
	}
}
