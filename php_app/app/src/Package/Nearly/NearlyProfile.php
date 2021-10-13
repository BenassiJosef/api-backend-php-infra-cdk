<?php

namespace App\Package\Nearly;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\User\UserBlocked;
use App\Models\User\UserProfileMacAddress;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Package\RequestUser\User;
use Doctrine\ORM\EntityManager;

class NearlyProfile
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getProfileFromMac(NearlyInput $input): ?UserProfile
    {
        /*
        if (!is_null($input->getProfileId())) {
         
            $profile = $this->entityManager->getRepository(UserProfile::class)->find($input->getProfileId());
            if (!is_null($profile)) {
                var_dump(count($profile->getMacAddresses()));
                if ($profile->getMacAddresses()) {
                }
            }
        }
*/

        $qb   = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr();

        $query = $qb
            ->select('m')
            ->from(UserProfileMacAddress::class, 'm')
            ->where($qb->expr()->orX(
                $expr->eq('m.macAddress', ':mac'),
                $expr->eq('m.macAddress', ':shaMac')
            ))
            ->setParameter('mac', $input->getMac())
            ->setParameter('shaMac', $input->getShaMac())
            ->setMaxResults(1)
            ->getQuery();

        /**
         * @var UserProfileMacAddress | null $result
         * @var QueryBuilder $query
         */
        $result = $query->getOneOrNullResult();

        if (is_null($result)) {
            return null;
        }

        return $result->getProfile();
    }

    public function isBlocked(string $serial, string $mac)
    {
        $userBlocked = $this->entityManager->createQueryBuilder()
            ->select('u.id')
            ->from(UserBlocked::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.mac = :mac')
            ->setParameter('serial', $serial)
            ->setParameter('mac', $mac)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        if (!empty($userBlocked)) {
            return true;
        }
        return false;
    }

    public function getOptIn(Organization $organisation, ?UserProfile $profile): OrganizationRegistration
    {
        if (is_null($profile)) {
            $p = new UserProfile();
            $p->id = 0;
            return new OrganizationRegistration($organisation, $p);
        }
        $registration = $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy([
            'profileId' => $profile->getId(),
            'organizationId' => $organisation->getId()
        ]);
        if (!is_null($registration)) {
            return $registration;
        }
        return new OrganizationRegistration($organisation, $profile);
    }

    public function setOptIn(Organization $organisation, UserProfile $profile, bool $data, bool $sms, bool $email)
    {
        /**
         *@var OrganizationRegistration $registration
         */
        $registration = $this->entityManager->getRepository(OrganizationRegistration::class)->findOneBy([
            'profileId' => $profile->getId(),
            'organizationId' => $organisation->getId()
        ]);
        $registration->setDataOptIn($data);
        $registration->setSmsOptIn($sms);
        $registration->setEmailOptIn($email);
        $this->entityManager->persist($registration);
        $this->entityManager->flush();
    }
}
