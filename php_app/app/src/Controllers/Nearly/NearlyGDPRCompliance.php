<?php
/**
 * Created by jamieaitken on 01/06/2018 at 13:35
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;


use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class NearlyGDPRCompliance
{
    protected $logger;
    protected $em;
    protected $userRegistrationRepository;

    /**
     * NearlyGDPRCompliance constructor.
     * @param Logger $logger
     * @param EntityManager $em
     * @param UserRegistrationRepository $userRegistrationRepository
     */
    public function __construct(Logger $logger, EntityManager $em, UserRegistrationRepository $userRegistrationRepository = null)
    {
        $this->logger = $logger;
        $this->em = $em;
        if (is_null($userRegistrationRepository)) {
            $userRegistrationRepository = new UserRegistrationRepository($this->logger, $this->em);
        }
        $this->userRegistrationRepository = $userRegistrationRepository;
    }

    public function compliant(string $serial, string $profileId)
    {
        // default to opted out of everything
        $return = [
            'location'  => false,
            'marketing' => false
        ];

        $hasOptedOutOfMarketing = $this->em->createQueryBuilder()
            ->select('u.type, u.optOut')
            ->from(MarketingOptOut::class, 'u')
            ->where('u.uid = :profile')
            ->andWhere('u.serial = :serial')
            ->setParameter('profile', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        $hasOptedOutLocation = $this->em->createQueryBuilder()
            ->select('u.deleted')
            ->from(LocationOptOut::class, 'u')
            ->where('u.profileId = :id')
            ->andWhere('u.serial = :serial')
            ->setParameter('id', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($hasOptedOutLocation)) {
            // there's no current location opt out so create one as "opted in" to the location
            $newOptedOutLocation          = new LocationOptOut($profileId, $serial);
            $newOptedOutLocation->deleted = true;

            $this->userRegistrationRepository->updateLocationOptOut($profileId, $serial, false);

            $this->em->persist($newOptedOutLocation);

            $this->em->flush();
            // we will still send back false as opted in so the front end triggers the t&cs
        } else {
            // there is an existing location opt
            if ($hasOptedOutLocation[0]['deleted']) {
                // deleted means that we opted into the location
                $return['location']  = true;
                // if we've opted into the location then default to opted into marketing
                // this will be overridden later by marketing opt outs
                $return['marketing'] = true;
            } else {
                // we opted out of the location
                $return['location']  = false;
                // default to no marketing
                $return['marketing'] = false;
            }
        }

        if (empty($hasOptedOutOfMarketing)) {
            // we don't have any current marketing options - create opted in entries for sms and email
            $newOptedOutSMS         = new MarketingOptOut($profileId, $serial, 'sms');
            $newOptedOutSMS->optOut = false;
            $this->em->persist($newOptedOutSMS);

            $newOptedOutEmail         = new MarketingOptOut($profileId, $serial, 'email');
            $newOptedOutEmail->optOut = false;
            $this->em->persist($newOptedOutEmail);

            $this->userRegistrationRepository->updateMarketingOptOuts($profileId, $serial, false, false);
            $this->em->flush();
            // we will send back false to trigger the marketing opt in
        } else {
            // SMS and EMAIL opt outs are always in sync so just take the first one
            $return['marketing'] = !$hasOptedOutOfMarketing[0]['optOut'];
        }
        return $return;
    }
}