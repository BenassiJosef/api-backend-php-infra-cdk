<?php


namespace App\Package\DataSources;


use App\Controllers\Integrations\Hooks\_HooksController;
use App\Models\DataSources\InteractionProfile;
use App\Models\UserProfile;
use App\Package\Async\Queue;
use Doctrine\ORM\EntityManager;
use Kreait\Firebase\RemoteConfig\User;

class NotifyingProfileInteraction implements ProfileSaver
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var ProfileInteraction $profileInteraction
	 */
	private $profileInteraction;

	/**
	 * @var Queue $notificationsQueue
	 */
	private $notificationsQueue;

	/**
	 * @var SingleProfileResponse | null $lastInsertedProfileInformation
	 */
	private $lastInsertedProfileInformation = null;

	/**
	 * @var _HooksController $hooksController
	 */
	private $hooksController;

	/**
	 * NotifyingProfileInteraction constructor.
	 * @param EntityManager $entityManager
	 * @param ProfileInteraction $profileInteraction
	 * @param Queue $notificationsQueue
	 * @param _HooksController $hooksController
	 */
	public function __construct(
		EntityManager $entityManager,
		ProfileInteraction $profileInteraction,
		Queue $notificationsQueue,
		_HooksController $hooksController
	) {
		$this->entityManager      = $entityManager;
		$this->profileInteraction = $profileInteraction;
		$this->notificationsQueue = $notificationsQueue;
		$this->hooksController    = $hooksController;
	}

	/**
	 * @return ProfileInteraction
	 */
	public function getProfileInteraction(): ProfileInteraction
	{
		return $this->profileInteraction;
	}

	/**
	 * @inheritDoc
	 */
	public function saveCandidateProfile(CandidateProfile $profile, OptInStatuses $optInStatusesOverride = null)
	{
		$this->profileInteraction->saveCandidateProfile($profile);
		$this->notify();
	}

	/**
	 * @inheritDoc
	 */
	public function saveEmail(string $email, OptInStatuses $optInStatuses = null)
	{
		$this->profileInteraction->saveEmail($email, $optInStatuses);
		$this->notify();
	}

	/**
	 * @inheritDoc
	 */
	public function saveProfileId(int $profileId)
	{
		$this->profileInteraction->saveProfileId($profileId);
		$this->notify();
	}

	/**
	 * @inheritDoc
	 */
	public function saveUserProfile(UserProfile $userProfile)
	{
		$this->profileInteraction->saveUserProfile($userProfile);
		$this->notify();
	}

	private function getSavedProfile(): UserProfile
	{
		/** @var UserProfile $profile */
		$profile = from($this->profileInteraction->profiles())
			->select(
				function (UserProfile $profile) {
					return $profile;
				}
			)
			->first();
		return $profile;
	}

	private function getNewSerials(UserProfile $profile): array
	{
		$query = 'SELECT `rs`.`serial`
                    FROM `organization_registration` `or`
                LEFT JOIN `registration_source` `rs` 
                ON `or`.`id` = `rs`.`organization_registration_id`
                WHERE `or`.`profile_id` = :profileId
                AND `or`.`organization_id` = :organizationId
                AND `rs`.`serial` IS NOT NULL
                GROUP BY `or`.`id`, `rs`.`serial`
                HAVING SUM(`rs`.`interactions`) = 1;';

		$statement = $this
			->entityManager
			->getConnection()
			->prepare($query);

		$organizationId = $this
			->profileInteraction
			->getInteractionRequest()
			->getOrganization()
			->getId()
			->toString();
		$profileId      = $profile->getId();
		$statement->bindParam('profileId', $profileId);
		$statement->bindParam('organizationId', $organizationId);
		$statement->execute();
		return from($statement->fetchAll())
			->select(
				function (array $row) {
					return $row['serial'];
				}
			)->toArray();
	}

	private function getProfileInformation(): SingleProfileResponse
	{
		$profile = $this->getSavedProfile();
		$serials = $this->getNewSerials($profile);
		return new SingleProfileResponse(
			$profile,
			$serials,
		);
	}

	private function sendQueueNotification(array $serials, UserProfile $profile)
	{
		$messages = [];
		foreach ($serials as $serial) {
			$messages[] = [
				'notificationContent' => [
					'objectId'  => $profile->getId(),
					'title'     => 'User registration',
					'kind'      => 'capture_registered',
					'link'      => '/analytics/registrations',
					'profileId' => $profile->getId(),
					'serial'    => $serial,
					'message'   => $profile->getEmail() . ' has just registered'
				]
			];
		}
		if (count($messages) === 0) {
			return;
		}
		$this->notificationsQueue->sendMessagesJson($messages);
	}

	private function notify()
	{
		$this->lastInsertedProfileInformation = $this->getProfileInformation();
		$insertedSerials                      = $this->lastInsertedProfileInformation->getSerials();
		$profile                              = $this->lastInsertedProfileInformation->getUserProfile();
		$this->sendQueueNotification($insertedSerials, $profile);
		foreach ($insertedSerials as $serial) {
			$this->notifySerial($serial, $profile);
		}
	}

	private function notifySerial(string $serial, UserProfile $profile)
	{
		$this->hooksController->serialToHook($serial, 'registration_unvalidated', $profile->zapierSerialize($serial));
	}

	/**
	 * @return SingleProfileResponse|null
	 */
	public function getLastInsertedProfileInformation(): ?SingleProfileResponse
	{
		return $this->lastInsertedProfileInformation;
	}
}
