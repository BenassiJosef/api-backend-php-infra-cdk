<?php


namespace App\Package\WebForms;


use App\Controllers\Clients\_ClientsController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Models\GiftCardSettings;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\GiftCard\GiftCardCreationInput;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;

class EmailSignupService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var _RegistrationsController $registrationsController
     */
    private $registrationsController;

    /**
     * @var _ClientsController $clientsController
     */
    private $clientsController;

    /**
     * EmailSignupService constructor.
     * @param EntityManager $entityManager
     * @param _RegistrationsController $registrationsController
     * @param _ClientsController $clientsController
     */
    public function __construct(
        EntityManager $entityManager,
        _RegistrationsController $registrationsController,
        _ClientsController $clientsController
    ) {
        $this->entityManager           = $entityManager;
        $this->registrationsController = $registrationsController;
        $this->clientsController       = $clientsController;
    }


    /**
     * @param Organization $organization
     * @param EmailSignupInput $input
     * @return UserProfile
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function createOrRegisterProfile(Organization $organization, EmailSignupInput $input): UserProfile
    {
        /** @var UserProfile | null $profile */
        $profile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => $input->getEmail()
                ]
            );
        if ($profile !== null) {
            $profile->setFirst($input->getFirst() ?? $profile->getFirst());
            $profile->setLast($input->getLast() ?? $profile->getLast());
            $profile->setPhone($input->getPhone() ?? $profile->getPhone());
            $profile->setBirthMonth($input->getBirthMonth() ?? $profile->getBirthMonth());
            $profile->setBirthDay($input->getBirthDay() ?? $profile->getBirthDay());
            $profile->setGender($input->getGender() ?? $profile->getGender());
            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            return $profile;
        }
        $locations = $this->locationsForOrganization($organization);
        if (count($locations) === 0) {
            throw new Exception("Organization has no locations attached");
        }
        $profileArray = null;
        foreach ($locations as $location) {
            $profileArray = $this->registerUser($input->jsonSerialize(), $location->getSerial());
        }
        if (!is_array($profileArray) || !array_key_exists('id', $profileArray)) {
            throw new Exception("Cannot create profile for user");
        }
        /** @var UserProfile $profile */
        $profile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->find($profileArray['id']);

        return $profile;
    }

    /**
     * @param array $profile
     * @param string $serial
     * @return array|bool
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function registerUser(array $profile, string $serial)
    {
        $profile = $this
            ->registrationsController
            ->updateOrCreate($profile, $serial);

        $this->clientsController->trackRegistration($serial, $profile->getId(), 0);

        return $profile;
    }

    /**
     * @param Organization $organization
     * @return LocationSettings[]
     */
    private function locationsForOrganization(Organization $organization): array
    {
        return iterator_to_array($organization->getLocations());
    }
}
