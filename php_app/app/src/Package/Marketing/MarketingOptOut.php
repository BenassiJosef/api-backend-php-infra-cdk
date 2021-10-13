<?php

namespace App\Package\Marketing;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\MarketingCampaigns;
use App\Models\Marketing\MarketingDeliverable;
use App\Models\Marketing\MarketingDeliverableEvent;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Pagination\PaginatedResponse;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\OrderBy;
use Exception;

/**
 * Class MarketingOptOutxception
 * @package App\Package\Marketing
 */
class MarketingOptOutException extends Exception
{
}

class MarketingOptOut
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * MarketingReportRepository constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function getOrganizationRegistrationFromCampaign(
		string $campaignId,
		int $profileId
	): OrganizationRegistration {
		/**
		 *@var MarketingCampaigns $campaign
		 */
		$campaign = $this->entityManager->getRepository(MarketingCampaigns::class)->find($campaignId);

		if (is_null($campaign)) {
			throw new MarketingOptOutException("campaign not found");
		}
		return $this->getOrganizationRegistration($campaign->getOrganizationId()->toString(), $profileId);
	}

	public function getOrganizationRegistration(string $organisationId, int $profileId): OrganizationRegistration
	{

		/**
		 *@var OrganizationRegistration $registration
		 */
		$registration = $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy([
			'organizationId' => $organisationId,
			'profileId' => $profileId,
		]);

		if (is_null($registration)) {
			/**
			 * @var Organization $organisation
			 */
			$organisation = $this->entityManager->getRepository(Organization::class)->findOneBy(
				['id' => $organisationId]
			);
			/**
			 * @var UserProfile $profile
			 */
			$profile = $this->entityManager->getRepository(UserProfile::class)->find(
				$profileId
			);
			$registration = new OrganizationRegistration($organisation, $profile);
		}

		return $registration;
	}

	public function setDataOpt(string $organisationId, int $profileId, bool $optIn): OrganizationRegistration
	{
		try {
			$registration = $this->getOrganizationRegistration($organisationId, $profileId);
			$registration->setDataOptIn($optIn);
			if (!$optIn) {
				$registration->setEmailOptIn($optIn);
				$registration->setSmsOptIn($optIn);
			}

			$this->entityManager->persist($registration);
			$this->entityManager->flush();

			return $registration;
		} catch (MarketingOptOutException $ex) {
			throw $ex;
		}
	}

	public function setOptFromEmail(string $campaignId, int $profileId, bool $emailOptIn)
	{
		try {
			$registration = $this->getOrganizationRegistrationFromCampaign($campaignId, $profileId);
			$registration->setEmailOptIn($emailOptIn);
			$this->createOrRemoveDeliverableEvent($campaignId, $profileId, $emailOptIn, 'email');

			$this->entityManager->persist($registration);
			$this->entityManager->flush();
		} catch (MarketingOptOutException $ex) {
			throw $ex;
		}
	}

	public function setOptFromSms(string $campaignId, int $profileId, bool $smsOptIn)
	{
		try {
			$registration = $this->getOrganizationRegistrationFromCampaign($campaignId, $profileId);
			$registration->setSmsOptIn($smsOptIn);
			$this->createOrRemoveDeliverableEvent($campaignId, $profileId, $smsOptIn, 'sms');
			$this->entityManager->persist($registration);
			$this->entityManager->flush();
		} catch (MarketingOptOutException $ex) {
			throw $ex;
		}
	}

	public function createOrRemoveDeliverableEvent(string $campaignId, int $profileId, bool $optIn, string $type)
	{
		/**
		 * @var MarketingDeliverable|null $delivery
		 */
		$delivery = $this->entityManager->getRepository(MarketingDeliverable::class)->findOneBy([
			'campaignId' => $campaignId,
			'profileId' => $profileId,
			'type' => $type,
		]);

		if (is_null($delivery)) {
			return;
		}

		$time = new DateTime();
		if ($optIn) {
			$findEvent = $this->entityManager->getRepository(MarketingDeliverableEvent::class)->findOneBy([
				'marketingDeliverableId' => $delivery->getId(),
				'event' => 'opt_out',
			]);
			if (!is_null($findEvent)) {
				$this->entityManager->remove($findEvent);
			}
		} else {
			$newDeliverableEvent = new MarketingDeliverableEvent($delivery->getId(), 'opt_out', $time->getTimestamp(), '');
			$this->entityManager->persist($newDeliverableEvent);
		}
	}

	public function setOptUsingEmail(string $organisationId, string $email, bool $emailOptIn, bool $smsOptIn)
	{
		/**
		 * @var UserProfile $profile
		 * */
		$profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy(['email' => $email]);
		if (is_null($profile)) {
			throw new MarketingOptOutException("no profile found");
		}

		$registration = $this->getOrganizationRegistration($organisationId, $profile->getId());
		$registration->setEmailOptIn($emailOptIn);
		$registration->setSmsOptIn($smsOptIn);

		$this->entityManager->persist($registration);
		$this->entityManager->flush();

		return $registration->jsonSerialize();
	}

	public function getOptOuts(string $organisationId, int $limit, int $offset, string $email = null)
	{
		$queryBuilder = $this
			->entityManager
			->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$query = $queryBuilder
			->select('c')
			->from(OrganizationRegistration::class, 'c')
			->leftJoin(UserProfile::class, 'p', 'WITH', 'p.id = c.profileId')
			->where($expr->eq('c.organizationId', ':organizationId'))
			->andWhere($expr->isNull('c.emailOptInAt'))
			->andWhere($expr->isNotNull('p.email'))
			->setParameter('organizationId', $organisationId);

		if (!is_null($email)) {
			$query = $query
				->andWhere($expr->like('p.email', $expr->literal($email . '%')));
		}

		$query = $query->orderBy(new OrderBy('c.createdAt', 'DESC'))
			->setMaxResults($limit)
			->setFirstResult($offset)->getQuery();

		return new PaginatedResponse($query);
	}
}
