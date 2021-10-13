<?php


namespace App\Package\GiftCard;


use App\Controllers\Clients\_ClientsController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\GiftCard;
use App\Models\GiftCardSettings;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;

class GiftCardService
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var ProfileInteractionFactory $profileInteractionFactory
	 */
	private $profileInteractionFactory;

	/**
	 * GiftCardFactory constructor.
	 * @param EntityManager $entityManager
	 * @param ProfileInteractionFactory $profileInteractionFactory
	 */
	public function __construct(
		EntityManager $entityManager,
		ProfileInteractionFactory $profileInteractionFactory
	) {
		$this->entityManager             = $entityManager;
		$this->profileInteractionFactory = $profileInteractionFactory;
	}

	/**
	 * @param GiftCardSettings $settings
	 * @param GiftCardCreationInput $input
	 * @return GiftCard
	 * @throws Exception
	 */
	public function giftCard(GiftCardSettings $settings, GiftCardCreationInput $input): GiftCard
	{
		$registration = $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy(
			[
				'profileId'      => $this->getProfile(
					$settings->getOrganization(),
					$input->getCandidateProfile()
				)->getId(),
				'organizationId' => $settings->getOrganization()->getId()
			]
		);
		return new GiftCard(
			$settings,
			$registration,
			$input->getAmount(),
			$input->getCurrency()
		);
	}

	/**
	 * @param GiftCard $card
	 * @param CandidateProfile $candidateProfile
	 * @return GiftCard
	 * @throws DBALException
	 * @throws MappingException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function changeOwner(GiftCard $card, CandidateProfile $candidateProfile): GiftCard
	{
		return $this
			->persist(
				$card
					->changeOwner(
						$this
							->getProfile(
								$card->getOrganization(),
								$candidateProfile
							)
					)
			);
	}

	private function persist(GiftCard $card): GiftCard
	{
		$this->entityManager->persist($card);
		$this->entityManager->flush();
		return $card;
	}

	/**
	 * @param GiftCardSettings $settings
	 * @param CandidateProfile $candidateProfile
	 * @return UserProfile
	 * @throws DBALException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws MappingException
	 */
	private function getProfile(Organization $organization, CandidateProfile $candidateProfile): UserProfile
	{
		$dataSource         = $this->profileInteractionFactory->getDataSource('gifting');
		$serials            = $this->serialsForOrganization($organization);
		$profileInteraction = $this
			->profileInteractionFactory
			->makeNotifyingProfileInteraction(
				new InteractionRequest(
					$organization,
					$dataSource,
					$serials,
				)
			);
		$profileInteraction
			->saveCandidateProfile($candidateProfile);
		return $profileInteraction
			->getLastInsertedProfileInformation()
			->getUserProfile();
	}

	private function serialsForOrganization(Organization $organization)
	{
		return from($organization->getLocations())
			->select(
				function (LocationSettings $locationSettings) {
					return $locationSettings->getSerial();
				}
			)
			->toArray();
	}
}
