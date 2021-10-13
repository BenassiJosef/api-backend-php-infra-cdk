<?php

namespace App\Package\Reviews\Controller;

use App\Controllers\Integrations\Mail\MailSender;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Reviews\ReviewSettings;
use App\Models\UserProfile;
use App\Package\DataSources\InteractionService;
use App\Package\Exceptions\BaseException;
use App\Package\Exceptions\InvalidUUIDException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use DoctrineExtensions\Query\Mysql\Date;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\Query\Expr\OrderBy;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Reviews\ReviewService;
use App\Package\Reviews\Scrapers\FacebookScraper;
use App\Package\Reviews\Scrapers\GoogleScraper;
use App\Package\Reviews\Scrapers\TripadvisorScraper;
use Slim\Http\StatusCode;
use StampedeTests\app\src\Package\Exceptions\NotFoundException;
use App\Package\Reviews\DelayedReviewSender;
use App\Package\Reviews\Exceptions\DuplicateUserReviewException;

class ReviewsSettingsController
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
	 * @var InteractionService $interactionService
	 */
	private $interactionService;

	/**
	 * @var MailSender $mailSender
	 */
	private $mailSender;

	/**
	 * @var ReviewService $reviewService
	 */
	private $reviewService;

	/**
	 * @var DelayedReviewSender $delayedReviewSender
	 */
	private $delayedReviewSender;

	/**
	 * ProfileChecker constructor.
	 * @param EntityManager $entityManager
	 * @param OrganizationProvider $organizationProvider
	 * @param MailSender $mailSender
	 * @param ReviewService $reviewService
	 * @param DelayedReviewSender $delayedReviewSender
	 */
	public function __construct(
		EntityManager $entityManager,
		OrganizationProvider $organizationProvider,
		InteractionService $interactionService,
		MailSender $mailSender,
		ReviewService $reviewService,
		DelayedReviewSender $delayedReviewSender
	) {
		$this->entityManager        = $entityManager;
		$this->organizationProvider = $organizationProvider;
		$this->interactionService   = $interactionService;
		$this->reviewService        = $reviewService;
		$this->mailSender           = $mailSender;
		$this->delayedReviewSender  = $delayedReviewSender;
	}

	public function sendDelayedReviewsEmail(Request $request, Response $response): Response
	{
		$interactionId = $request->getParsedBodyParam('interactionId');
		if ($interactionId === null) {
			throw new InvalidUUIDException(Uuid::NIL, 'interactionId');
		}

		$interactionUuid = Uuid::fromString($interactionId);

		$organization = $this
			->organizationProvider
			->organizationForRequest($request);

		$profileId = $request->getAttribute('profileId');
		if ($profileId === null) {
			throw new NotFoundException();
		}
		/** @var OrganizationRegistration | null $organizationRegistration */
		$organizationRegistration = $this
			->entityManager
			->getRepository(OrganizationRegistration::class)
			->findOneBy(
				[
					'organizationId' => $organization->getId(),
					'profileId'      => $profileId,
				]
			);
		if ($organizationRegistration === null) {
			throw new NotFoundException();
		}
		$this
			->delayedReviewSender
			->send(
				$organizationRegistration->getProfile(),
				$organization->getId(),
				$interactionUuid
			);
		return $response;
	}


	public function sendReviewEmail(Request $request, Response $response): Response
	{
		$settings  = $this
			->reviewService
			->getOrganizationReviewSettingsFromRequest($request);
		$profileId = $request->getAttribute('profileId');
		if ($profileId === null) {
			throw new NotFoundException();
		}
		/** @var UserProfile | null $userProfile */
		$userProfile = $this
			->entityManager
			->getRepository(UserProfile::class)
			->find($profileId);

		if ($userProfile === null) {
			throw new NotFoundException();
		}

		if (!$this->reviewService->canReview($settings, $userProfile->getId())) {
			throw new DuplicateUserReviewException($userProfile->getId());
		}

		$now             = new DateTimeImmutable('now');
		$nowTimestamp    = $now->getTimestamp();
		$sentAtTimestamp = $nowTimestamp - $settings->getSendAfterSecs() + 10; // add a 10s fudge factor, if we're too fast
		$sentAt          = $now->setTimestamp($sentAtTimestamp);

		$interactions = $this
			->interactionService
			->getRecentInteractions(
				$settings->getOrganization(),
				$userProfile,
				$sentAt
			);

		if (count($interactions) !== 0) {
			return $response->withStatus(StatusCode::HTTP_OK);
		}

		$resp = $this->reviewService->sendEmail(
			$settings,
			$userProfile
		);
		return $response->withJson($resp);
	}

	public function getAllReviewSettings(Request $request, Response $response): Response
	{
		$organisation = $this->organizationProvider->organizationForRequest($request);
		$limit          = (int)$request->getQueryParam('limit', 10);
		$offset         = (int)$request->getQueryParam('offset', 0);
		$queryBuilder   = $this
			->entityManager
			->createQueryBuilder();

		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('c')
			->from(ReviewSettings::class, 'c')
			->where($expr->eq('c.organizationId', ':organizationId'))
			->andWhere('c.deletedAt IS NULL');

		if ($organisation->getIsRestrictedByLocation()) {
			$query = $query->andWhere($expr->in('c.serial', ':serials'))
				->setParameter('serials', $organisation->getAccessableSerials());
		}

		$query = 			$query->orderBy(new OrderBy('c.createdAt', 'DESC'))
			->setMaxResults($limit)
			->setFirstResult($offset)
			->setParameter('organizationId', $organisation->getId())
			->getQuery();

		$resp = new PaginatedResponse($query);

		return $response->withJson($resp);
	}

	public function getReviewSettings(Request $request, Response $response): Response
	{
		$reviewSettings = $this->reviewService->getSettingsFromRequest($request);

		return $response->withJson($reviewSettings);
	}

	public function deleteReviewSettings(Request $request, Response $response): Response
	{

		$reviewSettings = $this->reviewService->getSettingsFromRequest($request);

		$reviewSettings->setDeleted(true);
		$this->entityManager->flush();

		return $response->withJson($reviewSettings);
	}

	public function createReviewSettings(Request $request, Response $response): Response
	{
		$organisation = $this->organizationProvider->organizationForRequest($request);
		if ($organisation->getIsRestrictedByLocation()) {
			throw new BaseException('not able to make this action', StatusCode::HTTP_BAD_REQUEST);
		}
		$params = ReviewSettings::fromArray(
			$this
				->organizationProvider
				->organizationForRequest($request),
			$request->getParsedBody()
		);

		$this->entityManager->persist($params);
		$this->entityManager->flush();


		return $response->withJson($params);
	}

	public function updateReviewSettings(Request $request, Response $response): Response
	{

		$reviewSettings = $this->reviewService->getSettingsFromRequest($request);

		$params = $reviewSettings
			->updateFromArray(
				$request->getParsedBody()
			);

		if ($reviewSettings->getTripadvisorUrl() !== $params->getTripadvisorUrl()) {
			$tripadvisorScraper = new TripadvisorScraper($this->entityManager, $this->reviewService);
			$tripadvisorScraper->scrape($params);
		}
		if ($reviewSettings->getFacebookPageId() !== $params->getFacebookPageId()) {
			$facebookScraper = new FacebookScraper($this->entityManager, $this->reviewService);
			$facebookScraper->scrape($params);
		}
		if ($reviewSettings->getGooglePlaceId() !== $params->getGooglePlaceId()) {
			$googleScraper = new GoogleScraper($this->entityManager, $this->reviewService);
			$googleScraper->scrape($params);
		}


		$this->entityManager->persist($params);
		$this->entityManager->flush();
		if ($reviewSettings->getTemplateId() !== $params->getTemplateId()) {
			$this->entityManager->clear();
		}

		return $response->withJson($this->reviewService->getSettingsFromRequest($request));
	}


	public function migrate(Request $request, Response $response): Response
	{
		return $response->withJson($this->reviewService->migrate());
	}

	public function sendPreview(Request $request, Response $response): Response
	{
		$reviewSettings = $this->reviewService->getSettingsFromRequest($request);
		$email          = $request->getParsedBodyParam('email', null);
		$name           = $request->getParsedBodyParam('name', '');
		$profileId      = $request->getParsedBodyParam('profile_id', null);
		$resp           = $this->sendEmail($reviewSettings, $email, $name, $profileId);

		return $response->withJson($resp);
	}

	public function sendEmail(ReviewSettings $settings, string $email, string $name = '', int $profileId = null)
	{
		$this
			->mailSender
			->send(
				[['name' => $name, 'to' => $email]],
				array_merge($settings->emailArray(), ['profile_id' => $profileId]),
				"ReviewTemplate",
				$settings->getSubject()
			);
		return $settings->emailArray();
	}
}
