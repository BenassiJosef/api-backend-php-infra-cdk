<?php

namespace StampedeTests\Helpers;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationOptOut;
use App\Models\Locations\LocationPolicyGroup;
use App\Models\Locations\LocationPolicyGroupSerials;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Marketing\CampaignSerial;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Models\Marketing\MarketingOptOut;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEvents;
use App\Models\MarketingLocations;
use App\Models\MarketingMessages;
use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User\UserDevice;
use App\Models\UserData;
use App\Models\UserPayments;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class EntityHelpers
{
	public static function createRootOrg(EntityManager $entityManager): Organization
	{
		$patrick = self::createOauthUser(
			$entityManager,
			"patrick@stampede.ai",
			"",
			"",
			"",
			"Patrick",
			"Clover"
		);

		$stampedeOrg = self::createOrganisation($entityManager, "Stampede root Org", $patrick);
		$stampedeOrg->setType(Organization::RootType);
		$entityManager->persist($stampedeOrg);
		$entityManager->flush();
		return $stampedeOrg;
	}

	public static function createCampaign(EntityManager $em,  Organization $organisation, string $name, ?string $filterId, ?string $eventId, string $messageId, bool $active, bool $deleted, ?int $limit, bool $automation): MarketingCampaigns
	{
		$campaign             = new MarketingCampaigns($name, $eventId, $filterId, $messageId, $active, $organisation);
		$campaign->deleted    = $deleted;
		$campaign->automation = $automation;
		$campaign->edited     = new \DateTime();
		$campaign->limit      = $limit;
		if (is_null($limit)) {
			$campaign->hasLimit = false;
		} else {
			$campaign->hasLimit = true;
		}
		$em->persist($campaign);
		$em->flush();

		return $campaign;
	}

	public static function createUser(EntityManager $em, string $email, string $phone, string $gender, ?int $birthMonth = null, ?int $birthDay = null): UserProfile
	{
		$up1             = new UserProfile();
		$up1->email      = $email;
		$up1->phone      = $phone;
		$up1->phoneValid = true;
		$up1->gender     = $gender;
		$up1->birthMonth = $birthMonth;
		$up1->birthDay   = $birthDay;
		$em->persist($up1);
		$em->flush();

		return $up1;
	}

	public static function createOrganizationRegistration(EntityManager $em, Organization $organisation, UserProfile $profile): OrganizationRegistration
	{
		$up1             = new OrganizationRegistration($organisation, $profile);

		$em->persist($up1);
		$em->flush();

		return $up1;
	}

	public static function createUserData(EntityManager $em, UserProfile $profile, string $serial, string $mac = null, string $type = null): UserData
	{
		$ud             = new UserData();
		$ud->profileId  = $profile->id;
		$ud->lastupdate = new \DateTime();
		$ud->timestamp  = new \DateTime();
		$ud->serial     = $serial;
		$ud->mac        = $mac;
		$ud->type       = $type;
		$ud->auth       = true;
		$em->persist($ud);
		$em->flush();

		return $ud;
	}

	public static function createUserDevice(EntityManager $em, UserProfile $profile, string $mac, string $type): UserDevice
	{
		$ud = new UserDevice([
			'mac'        => $mac,
			'mobile'     => 'a',
			'brand'      => 'a',
			'model'      => 'a',
			'short_name' => 'a',
			'type'       => $type
		]);
		$em->persist($ud);
		$em->flush();

		return $ud;
	}

	public static function createPayment(EntityManager $em, UserProfile $user, string $serial, string $planId, int $duration = 1, int $paymentAmount = 200, int $devices = 1, \DateTime $creationDate = null)
	{
		$p = new UserPayments(
			$user->email,
			$serial,
			$duration,
			$paymentAmount,
			$user->id,
			$devices,
			$planId,
			$creationDate
		);
		$em->persist($p);
		$em->flush();

		return $p;
	}

	public static function createCampaignSerial(EntityManager $em, MarketingCampaigns $campaign, string $serial): CampaignSerial
	{
		$cs = new CampaignSerial($campaign->id, $serial);
		$em->persist($cs);
		$em->flush();

		return $cs;
	}

	public static function createMarketingEvent(EntityManager $em, int $profileId, string $serial, string $campaignId, \DateTime $timestamp = null): MarketingEvents
	{
		$me            = new MarketingEvents("", "", $profileId, $serial, "123", $campaignId, "");
		$me->timestamp = $timestamp;
		$em->persist($me);
		$em->flush();

		return $me;
	}

	public static function createMarketingLocation(EntityManager $em, string $campaignId, string $serial): MarketingLocations
	{
		$ml = new MarketingLocations($campaignId, $serial);
		$em->persist($ml);
		$em->flush();

		return $ml;
	}

	public static function createNetworkAccess(EntityManager $em, string $serial, string $adminId): NetworkAccess
	{
		$na        = new NetworkAccess($serial);
		$na->admin = $adminId;
		$em->persist($na);
		$em->flush();

		return $na;
	}

	public static function createFilter(EntityManager $em, ?Organization $organization, string $key, string $value, string $operand, string $joinType = 'and'): FilterEventList
	{
		$filter = new FilterEventList($organization, 'test', 'search');
		$em->persist($filter);
		$em->flush();

		$filterCriteria = new IntegrationEventCriteria($filter->id, $key, $operand, $value, $joinType, null);
		$em->persist($filterCriteria);
		$em->flush();

		return $filter;
	}

	public static function createFilterCriteria(EntityManager $em, string $filterId, string $key, string $value, string $operand, string $joinType = 'and'): IntegrationEventCriteria
	{
		$filterCriteria = new IntegrationEventCriteria($filterId, $key, $operand, $value, $joinType, null);
		$em->persist($filterCriteria);
		$em->flush();

		return $filterCriteria;
	}

	public static function createUserRegistration(EntityManager $em, string $serial, int $profileId, \DateTime $registrationDate = null, int $connections = 1): UserRegistration
	{
		$ur = new UserRegistration($serial, $profileId, $registrationDate, $registrationDate, $connections);
		$em->persist($ur);
		$em->flush();

		return $ur;
	}

	public static function createMarketingMessage(EntityManager $em, Organization $organization, string $name): MarketingMessages
	{
		$marketingMessage                = new MarketingMessages($organization, $name);
		$marketingMessage->sendToSms     = true;
		$marketingMessage->smsContents   = "sms contents";
		$marketingMessage->smsSender     = "1234";
		$marketingMessage->sendToEmail   = true;
		$marketingMessage->emailContents = "email contents";
		$marketingMessage->templateType  = "mormal";
		$marketingMessage->subject       = "email subject";

		$em->persist($marketingMessage);
		$em->flush();

		return $marketingMessage;
	}

	public static function createMarketingOpt(EntityManager $em, int $id, string $serial, string $type, bool $optOut = true)
	{
		$marketingOpt         = new MarketingOptOut($id, $serial, $type);
		$marketingOpt->optOut = $optOut;
		$em->persist($marketingOpt);
		$em->flush();
	}

	public static function createLocationOptOut(EntityManager $em, string $profileId, string $serial, bool $deleted = false)
	{
		$locationOptOut          = new LocationOptOut($profileId, $serial);
		$locationOptOut->deleted = $deleted;
		$em->persist($locationOptOut);
		$em->flush();

		return $locationOptOut;
	}

	/**
	 * @param EntityManager $em The entity manager to use
	 * @param string $name The name of the policy group
	 * @param Organization $organization
	 * @param array $serials The array of serials to put in the group
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public static function createLocationPolicyGroup(EntityManager $em, string $name, Organization $organization, array $serials)
	{
		$group = new LocationPolicyGroup($organization, $name);
		$em->persist($group);
		foreach ($serials as $serial) {
			$groupSerial = new LocationPolicyGroupSerials($group->id, $serial);
			$em->persist($groupSerial);
		}
		$em->flush();
	}

	public static function createOauthUser(
		EntityManager $em,
		string $email,
		string $password,
		string $company = '',
		string $reseller = '',
		?string $firstName = null,
		?string $lastName = null,
		?string $stripeId = null
	) {
		$oauthUsers = new OauthUser($email, $password, $company, $reseller, $firstName, $lastName, $stripeId);
		$em->persist($oauthUsers);
		$em->flush();

		return $oauthUsers;
	}

	public static function createRole(EntityManager $em, string $name, ?Organization $organization, ?int $legacyId)
	{
		$role = new Role($name, $organization, $legacyId);
		$em->persist($role);
		$em->flush();

		return $role;
	}

	public static function createLocationSettings(
		EntityManager $em,
		string $serial,
		LocationOther $other,
		LocationBranding $branding,
		LocationWiFi $wifi,
		LocationPosition $location,
		LocationSocial $facebook,
		string $schedule,
		string $url,
		$freeQuestions,
		?Organization $organization
	) {
		$locationSetting = new LocationSettings(
			$serial,
			$other,
			$branding,
			$wifi,
			$location,
			$facebook,
			$schedule,
			$url,
			$freeQuestions,
			$organization
		);

		if (!is_null($organization)) {
			$organization->getLocations()->add($locationSetting);
			$em->persist($organization);
		}
		$em->persist($locationSetting);
		$em->flush();

		return $locationSetting;
	}

	public static function createOrganisation(EntityManager $em, string $name, OauthUser $oauthUser)
	{
		$org = new Organization($name, $oauthUser);
		$em->persist($org);
		$em->flush();
		return $org;
	}
}
