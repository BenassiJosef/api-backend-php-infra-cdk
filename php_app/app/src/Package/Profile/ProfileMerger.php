<?php


namespace App\Package\Profile;


use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\UserProfile;
use App\Package\RequestUser\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Throwable;

class ProfileMerger
{
	// Sections are left commented out as the tables are very big and slow
	private static $updateStatements = [
		'UPDATE `core`.`gift_card` gc SET gc.profile_id = :newProfileId WHERE gc.profile_id = :oldProfileId;',
		'UPDATE `core`.`website_profile_cookies` wpc SET wpc.profile_id = :newProfileId WHERE wpc.profile_id = :oldProfileId;',
		'UPDATE `core`.`marketing_deliverability` md SET md.profileId = :newProfileId WHERE md.profileId = :oldProfileId;',
		'UPDATE `core`.`nearly_impressions` ni SET ni.profileId = :newProfileId WHERE ni.profileId = :oldProfileId;',
		'UPDATE `core`.`marketing_event` me SET me.profileId = :newProfileId WHERE me.profileId = :oldProfileId;',
		'UPDATE `core`.`marketing_user_opt` muo SET muo.uid = :newProfileId WHERE muo.uid = :oldProfileId;',
		'UPDATE `core`.`nearly_story_page_activity` nspa SET nspa.profileId = :newProfileId WHERE nspa.profileId = :oldProfileId;',
		'UPDATE `core`.`stripeCustomer` sc SET sc.profileId = :newProfileId WHERE sc.profileId = :oldProfileId;',
		'UPDATE `core`.`user_data` ud SET ud.profileId = :newProfileId WHERE ud.profileId = :oldProfileId;',
		'UPDATE `core`.`user_opt_out` uoo SET uoo.profileId = :newProfileId WHERE uoo.profileId = :oldProfileId;',
		'UPDATE `core`.`user_payments` up SET up.profileId = :newProfileId WHERE up.profileId = :oldProfileId;',
		'UPDATE `core`.`user_profile_mac_addresses` apm SET apm.profile_id = :newProfileId WHERE apm.profile_id = :oldProfileId;',
		'UPDATE `core`.`interaction_profile` ip SET ip.profile_id = :newProfileId WHERE ip.profile_id = :oldProfileId;',
		'UPDATE `core`.`validation_sent` vs SET vs.profileId = :newProfileId WHERE vs.profileId = :oldProfileId;',
		'UPDATE `core`.`user_review` urr SET urr.profile_id = :newProfileId WHERE urr.profile_id = :oldProfileId;',
		'UPDATE `core`.`loyalty_stamp_card` lsc SET lsc.profile_id = :newProfileId WHERE lsc.profile_id = :oldProfileId;',
		'INSERT INTO `core`.`user_registrations` (serial, profile_id, number_of_visits, created_at, last_seen_at, location_opt_in_date, email_opt_in_date, sms_opt_in_date) 
(SELECT ur.serial, :newProfileId AS profile_id, ur.number_of_visits, ur.created_at, ur.last_seen_at, ur.location_opt_in_date, ur.email_opt_in_date, ur.sms_opt_in_date
FROM `core`.`user_registrations` ur WHERE ur.profile_id = :oldProfileId)
ON DUPLICATE KEY UPDATE 
user_registrations.number_of_visits = user_registrations.number_of_visits + VALUES(number_of_visits),
user_registrations.created_at=LEAST(COALESCE(VALUES(created_at), user_registrations.created_at), COALESCE(user_registrations.created_at, VALUES(created_at))), 
user_registrations.last_seen_at=GREATEST(COALESCE(VALUES(last_seen_at), user_registrations.last_seen_at), COALESCE(user_registrations.last_seen_at, VALUES(last_seen_at))),
user_registrations.location_opt_in_date=LEAST(COALESCE(VALUES(location_opt_in_date), user_registrations.location_opt_in_date), COALESCE(user_registrations.location_opt_in_date, VALUES(location_opt_in_date))),
user_registrations.email_opt_in_date=LEAST(COALESCE(VALUES(email_opt_in_date), user_registrations.email_opt_in_date), COALESCE(user_registrations.email_opt_in_date, VALUES(email_opt_in_date))),
user_registrations.sms_opt_in_date=LEAST(COALESCE(VALUES(sms_opt_in_date), user_registrations.sms_opt_in_date), COALESCE(user_registrations.sms_opt_in_date, VALUES(sms_opt_in_date)));'
	];

	private static $deleteStatements = [
		'DELETE FROM `core`.`user_profile_accounts` WHERE id = :oldId;',
		'DELETE FROM `core`.`user_registrations` WHERE profile_id = :oldId',
		'DELETE FROM `core`.`user_profile` WHERE id = :oldId;'
	];

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * ProfileMerger constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param UserProfile $from
	 * @param UserProfile $to
	 * @return UserProfile
	 * @throws Throwable
	 * @throws ConnectionException
	 */
	public function merge(UserProfile $from, UserProfile $to): UserProfile
	{
		$conn = $this->entityManager->getConnection();
		$this->entityManager->beginTransaction();
		foreach (self::$updateStatements as $statement) {
			try {
				$this->query($conn, $statement, $from, $to);
			} catch (Throwable $t) {
				$this->entityManager->rollBack();
				throw $t;
			}
		}
		$this->mergeOrganizationRegistrations($from, $to);
		$this->entityManager->flush();
		foreach (self::$deleteStatements as $statement) {
			try {
				$this->delete($conn, $statement, $from);
			} catch (Throwable $t) {
				$this->entityManager->rollBack();
				throw $t;
			}
		}
		$this->entityManager->commit();
		$this->entityManager->clear();
		/** @var UserProfile $merged */
		$merged = $this
			->entityManager
			->getRepository(UserProfile::class)
			->find($to->getId());
		return $merged;
	}

	public function mergeOrganizationRegistrations(UserProfile $from, UserProfile $to)
	{
		$fromRegistrations = $this->organizationRegistrationsForProfile($from);
		if (count($fromRegistrations) === 0) {
			// nothing to merge from, woo!
			return;
		}
		$toRegistrations = $this->organizationRegistrationsForProfile($to);
		$newKeys         = array_diff(array_keys($fromRegistrations), array_keys($toRegistrations));
		foreach ($newKeys as $key) {
			$reg = $fromRegistrations[$key];
			unset($fromRegistrations[$key]);
			$reg->setProfile($to);
			$toRegistrations[$key] = $reg;
			$this->entityManager->persist($reg);
		}
		if (count($fromRegistrations) === 0) {
			// there was no registration overlap, so woo!
			return;
		}
		foreach ($fromRegistrations as $orgId => $registration) {
			$this->mergeOrganizationRegistration($registration, $toRegistrations[$orgId]);
		}
	}


	private function mergeOrganizationRegistration(OrganizationRegistration $from, OrganizationRegistration $to)
	{
		$fromRegistrations = $this->registrationSourcesForOrganizationRegistration($from);
		if (count($fromRegistrations) === 0) {
			// nothing to transfer over, woo!
			return;
		}
		$toRegistrations = $this->registrationSourcesForOrganizationRegistration($to);
		$newKeys         = array_diff(array_keys($fromRegistrations), array_keys($toRegistrations));
		foreach ($newKeys as $key) {
			$reg = $fromRegistrations[$key];
			unset($fromRegistrations[$key]);
			$reg->setOrganizationRegistration($to);
			$toRegistrations[$key] = $reg;
		}
		if (count($fromRegistrations) === 0) {
			// no overlap between registrations
			return;
		}
		foreach ($fromRegistrations as $key => $registrationSource) {
			$toReg = $toRegistrations[$key];
			$toReg->addInteractions($registrationSource->getInteractions());
			$this->entityManager->remove($registrationSource);
		}
		$this->entityManager->remove($from);
	}

	/**
	 * @param OrganizationRegistration $organizationRegistration
	 * @return RegistrationSource[]
	 */
	private function registrationSourcesForOrganizationRegistration(OrganizationRegistration $organizationRegistration): array
	{
		return from($organizationRegistration->getRegistrations())
			->select(
				function (RegistrationSource $registrationSource): RegistrationSource {
					return $registrationSource;
				},
				function (RegistrationSource $registrationSource): string {
					return implode(
						'_',
						[
							$registrationSource->getDataSourceId()->toString(),
							$registrationSource->getSerial() ?? 'ORGANIZATION',
						]
					);
				}
			)
			->toArray();
	}

	/**
	 * @param UserProfile $profile
	 * @return OrganizationRegistration[]
	 */
	private function organizationRegistrationsForProfile(UserProfile $profile): array
	{
		/** @var  $organizationRegistrations */
		$organizationRegistrations = $this
			->entityManager
			->getRepository(OrganizationRegistration::class)
			->findBy(
				[
					'profileId' => $profile->getId(),
				]
			);
		return from($organizationRegistrations)
			->select(
				function (OrganizationRegistration $registration): OrganizationRegistration {
					return $registration;
				},
				function (OrganizationRegistration $registration): string {
					return $registration->getOrganizationId()->toString();
				}
			)
			->toArray();
	}

	private function delete(Connection $connection, string $query, UserProfile $old)
	{
		$statement = $connection->prepare($query);
		$oldId     = $old->getId();
		$statement->bindParam('oldId', $oldId);
		$statement->execute();
	}

	private function query(Connection $conn, string $query, UserProfile $from, UserProfile $to)
	{
		$statement = $conn->prepare($query);
		$oldId     = $from->getId();
		$statement->bindParam('oldProfileId', $oldId);
		$newId = $to->getId();
		$statement->bindParam('newProfileId', $newId);
		$statement->execute();
	}
}
