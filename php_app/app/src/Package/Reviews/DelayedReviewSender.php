<?php


namespace App\Package\Reviews;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Reviews\ReviewSettings;
use App\Models\UserProfile;
use App\Package\Clients\Delorean\DeloreanClient;
use App\Package\Clients\Delorean\Exceptions\FailedToScheduleJobException;
use App\Package\Clients\Delorean\Job;
use App\Package\Database\BaseStatement;
use App\Package\Database\RawStatementExecutor;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\UuidInterface;

class DelayedReviewSender
{
	/**
	 * @var DeloreanClient $deloreanClient
	 */
	private $deloreanClient;

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var RawStatementExecutor $database
	 */
	private $database;

	/**
	 * DelayedReviewSender constructor.
	 * @param DeloreanClient $deloreanClient
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		DeloreanClient $deloreanClient,
		EntityManager $entityManager
	) {
		$this->deloreanClient = $deloreanClient;
		$this->entityManager  = $entityManager;
		$this->database       = new RawStatementExecutor($entityManager);
	}


	/**
	 * @param UserProfile $profile
	 * @param UuidInterface $organizationId
	 * @param UuidInterface $interactionId
	 * @throws FailedToScheduleJobException
	 */
	public function send(
		UserProfile $profile,
		UuidInterface $organizationId,
		UuidInterface $interactionId
	) {
		$locationReviewSettings = $this->reviewSettings(
			$organizationId,
			$this->interactionSerials($interactionId)
		);

		/** @var OrganizationRegistration | null $organizationRegistration */
		$organizationRegistration = $this
			->entityManager
			->getRepository(OrganizationRegistration::class)
			->findOneBy(
				[
					'organizationId' => $organizationId,
					'profileId'      => $profile->getId(),
				]
			);

		if (is_null($organizationRegistration)) {
			return;
		}
		if (!$organizationRegistration->getEmailOptIn()) {
			return;
		}

		foreach ($locationReviewSettings as $reviewSetting) {
			if (!$reviewSetting->hasValidSubscription()) {
				continue;
			}
			$this
				->deloreanClient
				->scheduleHTTP(
					'reviews',
					$reviewSetting->getSendTime(),
					new Job(
						'backend',
						sprintf(
							'/organisations/%s/reviews/settings/%s/profile/%d/send',
							$organizationId->toString(),
							$reviewSetting->getId()->toString(),
							$profile->getId()
						),
						'POST'
					)
				);
		}
	}

	/**
	 * @param UuidInterface $organizationId
	 * @param array $serials
	 * @return ReviewSettings[]
	 */
	private function reviewSettings(UuidInterface $organizationId, array $serials)
	{
		return $this
			->entityManager
			->getRepository(ReviewSettings::class)
			->findBy(
				[
					'organizationId' => $organizationId->toString(),
					'serial'         => $serials,
					'deletedAt'      => null,
					'isActive'       => true,
				]
			);
	}

	private function interactionSerials(UuidInterface $interactionId): array
	{
		return $this
			->database
			->fetchFirstColumn(
				new BaseStatement(
					"SELECT 
	`is`.`serial` 
FROM interaction_serial `is` 
WHERE `is`.interaction_id = :interactionId;",
					[
						'interactionId' => $interactionId->toString(),
					]
				)
			);
	}
}
