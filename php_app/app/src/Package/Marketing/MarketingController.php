<?php

namespace App\Package\Marketing;

use App\Models\MarketingCampaigns;
use App\Models\MarketingMessages;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Service\ServiceRequest;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

class MarketingController
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var MarketingOptOut $optOut
	 */
	private $optOut;
	/**
	 * @var Campaign $campaign
	 */
	private $campaign;

	/**
	 * @var OrganizationProvider $organizationProvider
	 */
	private $organizationProvider;

	/**
	 * MarketingController constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		EntityManager $entityManager,
		OrganizationProvider $organizationProvider
	) {
		$this->entityManager = $entityManager;
		$this->optOut = new MarketingOptOut($this->entityManager);
		$this->campaign = new Campaign($this->entityManager);
		$this->organizationProvider = $organizationProvider;
	}

	public function getCampaign(Request $request, Response $response)
	{
		$campaignId = $request->getAttribute('id');
		$campaign = $this->campaign->get($campaignId);
		if (is_null($campaign)) {
			return $response->withJson('NOT_FOUND', 404);
		}

		return $response->withJson($campaign, 200);
	}

	public function createCampaign(Request $request, Response $response)
	{
		$data = $request->getParsedBody();
		$templateId = $request->getParsedBodyParam('templateId', null);
		$name = $request->getParsedBodyParam('name', null);

		if (is_null($templateId) || is_null($name)) {
			return $response->withJson('MISSING_INPUTS', 400);
		}

		$organization = $this->organizationProvider->organizationForRequest($request);
		$campaign = $this->campaign->create($data, $organization);
		if (is_null($campaign)) {
			return $response->withJson('NOT_FOUND', 404);
		}

		return $response->withJson($campaign, 200);
	}

	public function updateCampaign(Request $request, Response $response)
	{
		$campaign = $request->getParsedBody();
		$organization = $this->organizationProvider->organizationForRequest($request);
		$campaign = $this->campaign->update($campaign, $organization);
		if (is_null($campaign)) {
			return $response->withJson('NOT_FOUND', 404);
		}

		return $response->withJson($campaign, 200);
	}

	public function getCampaignReport(Request $request, Response $response)
	{
		$campaignId = $request->getAttribute('id');
		$report = new MarketingReportRepository($this->entityManager);
		$data = Http::status(200, $report->getCampaign($campaignId, true));

		return $response->withJson($data, $data['status']);
	}

	public function getOrganisationCampaignReport(Request $request, Response $response)
	{
		$organizationId = $request->getAttribute('orgId');
		$report = new MarketingReportRepository($this->entityManager);
		$data = Http::status(200, $report->getOrganisationCampaignReport($organizationId));

		return $response->withJson($data, $data['status']);
	}

	public function getCampaignEventReport(Request $request, Response $response)
	{
		$campaignId = $request->getAttribute('id');
		$event = $request->getAttribute('event', null);
		if (is_null($event) || ($event !== 'click' && $event !== 'open' && $event !== 'in_venue_visit')) {
			$data = Http::status(403, 'WRONG_EVENT_TYPE');
		} else {
			$report = new MarketingReportRepository($this->entityManager);
			$data = Http::status(200, $report->getCampaignEvent($campaignId, $event));
		}

		return $response->withJson($data, $data['status']);
	}

	public function fetchAllCampaigns(Request $request, Response $response): Response
	{
		$organizationId = $request->getAttribute('orgId');
		$limit = (int) $request->getQueryParam('limit', 10);
		$offset = (int) $request->getQueryParam('offset', 0);
		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('c')
			->from(MarketingCampaigns::class, 'c')
			->where($expr->eq('c.organizationId', ':organizationId'))
			->andWhere('c.deleted = :deleted')
			->andWhere('c.messageId IS NOT NULL')
			->andWhere('c.name NOT LIKE :reviewCampaign')
			->orderBy(new OrderBy('c.created', 'DESC'))
			->setMaxResults($limit)
			->setFirstResult($offset)
			->setParameter('reviewCampaign', 'REVIEW\_%')
			->setParameter('organizationId', $organizationId)
			->setParameter('deleted', false)
			->getQuery();

		$resp = new PaginatedResponse($query);
		$report = new MarketingReportRepository($this->entityManager);

		$body = $resp->getBody();
		foreach ($body as $campaign) {
			//$campaign->setReport($report->getCampaign($campaign->getId()));
		}

		return $response->withJson($resp);
	}

	public function fetchAllMessages(Request $request, Response $response): Response
	{
		$organizationId = $request->getAttribute('orgId');
		$limit = (int) $request->getQueryParam('limit', 10);
		$offset = (int) $request->getQueryParam('offset', 0);
		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('m')
			->from(MarketingMessages::class, 'm')
			->where($expr->eq('m.organizationId', ':organizationId'))
			->andWhere('m.templateType != :templateType')
			->andWhere('m.deleted = :deleted')
			->orderBy(new OrderBy('m.created', 'DESC'))
			->setMaxResults($limit)
			->setFirstResult($offset)
			->setParameter('organizationId', $organizationId)
			->setParameter('templateType', 'review')
			->setParameter('deleted', false)
			->getQuery();

		$resp = new PaginatedResponse($query);
		return $response->withJson($resp);
	}

	public function deleteMessage(Request $request, Response $response): Response
	{
		$messageId = $request->getAttribute('id');
		$message = new Message($this->entityManager);
		$resp = $message->delete($messageId);
		return $response->withJson($resp, $resp['status']);
	}

	public function getOptOuts(Request $request, Response $response): Response
	{
		$organizationId = $request->getAttribute('orgId');
		$limit = (int) $request->getQueryParam('limit', 10);
		$offset = (int) $request->getQueryParam('offset', 0);
		$email = $request->getQueryParam('email', null);
		try {
			return $response->withJson($this->optOut->getOptOuts($organizationId, $limit, $offset, $email), 200);
		} catch (MarketingOptOutException $ex) {
			return $response->withJson($ex->getMessage(), 403);
		}
	}

	public function optUsingServiceEmail(Request $request, Response $response): Response
	{
		$emailId = $request->getParam('email_id', null);
		$organizationId = $request->getParam('orgId', null);
		$emailOptIn = filter_var($request->getParam('email_opt_in'), FILTER_VALIDATE_BOOLEAN);
		$smsOptIn = filter_var($request->getParam('sms_opt_in'), FILTER_VALIDATE_BOOLEAN);

		if (is_null($organizationId) || is_null($emailId)) {
			return $response->withJson('INPUT_PARAMS_INVALID', 403);
		}
		if (is_null($request->getParam('email_opt_in', null))) {
			$emailOptIn = null;
		}

		if (is_null($request->getParam('sms_opt_in', null))) {
			$smsOptIn = null;
		}

		if (is_null($emailOptIn) && is_null($smsOptIn)) {
			return $response->withJson('INVALID_PARAMS', 403);
		}

		try {
			$service = new ServiceRequest();
			$email = $service->get("${organizationId}/emails/${emailId}");

			$registration = $this->optOut->getOrganizationRegistration($organizationId, $email->data->profile_id);
			if (!is_null($emailOptIn)) {
				$registration->setEmailOptIn($emailOptIn);
				try {
					$service->post("${organizationId}/emails/${emailId}/opt-out", []);
				} catch (Throwable $e) {
				}
			}
			if (!is_null($smsOptIn)) {
				$registration->setSmsOptIn($smsOptIn);
			}

			$this->entityManager->persist($registration);
			$this->entityManager->flush();

			return $response->withJson($registration);
		} catch (Throwable $e) {
			throw $e;
		}
	}

	public function optUsingEmail(Request $request, Response $response): Response
	{
		$email = $request->getQueryParam('email', null);
		$organizationId = $request->getAttribute('orgId', null);
		$emailOptIn = filter_var($request->getParam('email_opt_in'), FILTER_VALIDATE_BOOLEAN);
		$smsOptIn = filter_var($request->getParam('sms_opt_in'), FILTER_VALIDATE_BOOLEAN);

		if (is_null($organizationId) || is_null($email)) {
			return $response->withJson('INPUT_PARAMS_INVALID', 403);
		}
		if (is_null($request->getParam('email_opt_in', null))) {
			$emailOptIn = null;
		}

		if (is_null($request->getParam('sms_opt_in', null))) {
			$smsOptIn = null;
		}

		try {
			if (!is_null($emailOptIn) && !is_null($smsOptIn)) {
				return $response->withJson(
					$this->optOut->setOptUsingEmail($organizationId, $email, $emailOptIn, $smsOptIn),
					200
				);
			}

			return $response->withJson('INVALID_PARAMS', 403);
		} catch (MarketingOptOutException $ex) {
			return $response->withJson($ex->getMessage(), 403);
		}
	}

	public function optOut(Request $request, Response $response): Response
	{
		$campaignId = $request->getQueryParam('campaign_id', null);
		$profileId = (int) $request->getQueryParam('profile_id', null);
		$emailOptIn = filter_var($request->getParam('email_opt_in'), FILTER_VALIDATE_BOOLEAN);
		$smsOptIn = filter_var($request->getParam('sms_opt_in'), FILTER_VALIDATE_BOOLEAN);

		if (is_null($campaignId) || is_null($profileId)) {
			return $response->withJson('INPUT_PARAMS_INVALID', 403);
		}
		if (is_null($emailOptIn) && is_null($smsOptIn)) {
			return $response->withJson('OPT_IN_PARAMS_INVALID', 403);
		}
		if (is_null($request->getParam('sms_opt_in', null))) {
			$smsOptIn = null;
		}
		if (is_null($request->getParam('email_opt_in', null))) {
			$emailOptIn = null;
		}

		try {
			if (!is_null($emailOptIn)) {
				$this->optOut->setOptFromEmail($campaignId, $profileId, $emailOptIn);
			}
			if (!is_null($smsOptIn)) {
				$this->optOut->setOptFromSms($campaignId, $profileId, $smsOptIn);
			}
			return $response->withJson('OPT_SAVED', 200);
		} catch (MarketingOptOutException $ex) {
			return $response->withJson($ex->getMessage(), 403);
		}
	}
}
