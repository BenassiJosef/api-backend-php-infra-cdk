<?php

namespace App\Package\Domains\Registration;

use App\Models\UserRegistration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Kreait\Firebase\RemoteConfig\User;
use Monolog\Logger;

class UserRegistrationRepository {
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * UserRegistrationRepository constructor.
     * @param Logger $logger
     * @param EntityManager $em
     */
    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * TODO We have a weird mix in our code base of profileId being both int and string...
     * @param $profileId
     * @return int
     */
    private function getProfileIdInt($profileId): int
    {
        if (is_int($profileId)) {
            return $profileId;
        }
        if (is_string($profileId)) {
            $profileIdInt = intval($profileId);
            if ($profileIdInt == 0) {
                throw new \InvalidArgumentException("ProfileId must be a valid integer");
            }
            return $profileIdInt;
        }
    }

    /**
     * Opt in our out of email and sms marketing messages
     * @param int|string $profileIdAny
     * @param string $serial
     * @param bool $emailOptOut
     * @param bool $smsOptOut
     * @throws ORMException
     */
    public function updateMarketingOptOuts($profileIdAny, string $serial, bool $emailOptOut, bool $smsOptOut)
    {
        $optInEmailDate = $emailOptOut ? null : new \DateTime();
        $optInSMSDate = $smsOptOut ? null : new \DateTime();
        $registration      = $this->getUserRegistrtion($profileIdAny, $serial);
        if (is_null($registration))
        {
            return;
        }
        $registration->setEmailOptInDate($optInEmailDate);
        $registration->setSMSOptInDate($optInSMSDate);
        $this->em->persist($registration);
    }

    /**
     * Opt in and out of sms marketing messages
     * @param int|string $profileIdAny
     * @param string $serial
     * @param bool $optOut
     * @throws ORMException
     */
    public function updateSMSOptOut($profileIdAny, string $serial, bool $optOut)
    {
        $optInSMSDate = $optOut ? null : new \DateTime();
        $registration      = $this->getUserRegistrtion($profileIdAny, $serial);
        if (is_null($registration))
        {
            return;
        }
        $registration->setSMSOptInDate($optInSMSDate);
        $this->em->persist($registration);
    }

    /**
     * Opt in and out of email marketing messages
     * @param int|string $profileIdAny
     * @param string $serial
     * @param bool $optOut
     * @throws ORMException
     */
    public function updateEmailOptOut($profileIdAny, string $serial, bool $optOut)
    {
        $optInEmailDate = $optOut ? null : new \DateTime();
        $registration      = $this->getUserRegistrtion($profileIdAny, $serial);
        if (is_null($registration))
        {
            return;
        }
        $registration->setEmailOptInDate($optInEmailDate);
        $this->em->persist($registration);
    }

    /**
     * Opt in and out of the location
     * @param int|string $profileIdAny
     * @param string $serial
     * @param bool $optOut
     * @throws ORMException
     */
    public function updateLocationOptOut($profileIdAny, string $serial, bool $optOut)
    {
        $optInLocationDate = $optOut ? null : new \DateTime();
        $registration      = $this->getUserRegistrtion($profileIdAny, $serial);
        if (is_null($registration))
        {
            return;
        }
        $registration->setLocationOptInDate($optInLocationDate);
        $this->em->persist($registration);
    }

    /**
     * Opt out of email marketing, sms marketing and location
     * @param int|string $profileIdAny
     * @param array $serials
     * @param bool $optOutLocation
     * @param bool $optOutMarketingEmail
     * @param bool $optOutMarketingSMS
     * @throws \Exception
     */
    public function updateOptOuts($profileIdAny, array $serials, bool $optOutLocation, bool $optOutMarketingEmail, bool $optOutMarketingSMS)
    {
        $optInLocationDate = $optOutLocation ? null : new \DateTime();
        $optInSMSDate = $optOutMarketingSMS ? null : new \DateTime();
        $optInEMailDate = $optOutMarketingEmail ? null : new \DateTime();
        $profileId = $this->getProfileIdInt($profileIdAny);
        $qb = $this->em->createQueryBuilder();
        $qb->update(UserRegistration::class, 'ur')
            ->set('ur.emailOptInDate', ':optInEMailDate')
            ->set('ur.smsOptInDate', ':optInSMSDate')
            ->set('ur.locationOptInDate', ':optInLocationDate')
            ->where('ur.profileId = :profileId and ur.serial in (:serials)')
            ->setParameter('profileId', $profileId)
            ->setParameter('serials', $serials)
            ->setParameter("optInEMailDate", $optInEMailDate)
            ->setParameter('optInSMSDate', $optInSMSDate)
            ->setParameter('optInLocationDate', $optInLocationDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Get the user registration for this profile and serial
     * @param int|string $profileIdAny
     * @param string $serial
     * @return UserRegistration|null
     */
    private function getUserRegistrtion($profileIdAny,
                                        string $serial): ?UserRegistration
    {
        $profileId    = $this->getProfileIdInt($profileIdAny);
        $repository   = $this->em->getRepository(UserRegistration::class);
        /** @var UserRegistration $registration */
        $registration = $repository->findOneBy(["profileId" => $profileId, "serial" => $serial]);
        $this->logger->error("No user registration for profile $profileIdAny and serial $serial");
        // TODO this should not really happen
        // if (is_null($registration)) {
        //     throw new \InvalidArgumentException("No user registration for profile $profileIdAny");
        // }
        return $registration;
    }
}
