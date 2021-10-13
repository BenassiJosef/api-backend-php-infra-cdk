<?php
/**
 * Created by jamieaitken on 05/06/2018 at 16:41
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Nearly\NearlyProfileOptOut;
use App\Models\Locations\LocationOptOut;
use App\Models\Locations\LocationPolicyGroupSerials;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class OptOutWorker
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UserRegistrationRepository
     */
    private $userRegistrationRepository;
    /**
     * @var NearlyProfileOptOut
     */
    private $nearlyProfileOptOut;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * OptOutWorker constructor.
     * @param Logger $logger
     * @param EntityManager $em
     * @param UserRegistrationRepository|null $userRegistrationRepository
     * @param NearlyProfileOptOut|null $nearlyProfileOptOut
     */
    public function __construct(Logger $logger, EntityManager $em, UserRegistrationRepository $userRegistrationRepository = null, NearlyProfileOptOut $nearlyProfileOptOut = null)
    {
        $this->logger = $logger;
        $this->em = $em;
        if (is_null($userRegistrationRepository)) {
            $userRegistrationRepository = new UserRegistrationRepository($logger, $em);
        }
        $this->userRegistrationRepository = $userRegistrationRepository;
        if (is_null($nearlyProfileOptOut)) {
            $nearlyProfileOptOut = new NearlyProfileOptOut($this->em);
        }
        $this->nearlyProfileOptOut = $nearlyProfileOptOut;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $serial          = $body['serial'];
        $profileId       = (string)$body['id'];
        $optOutLocation  = $body['optOutLocation'];
        $optOutMarketing = $body['optOutMarketing'];

        $serials = $this->getSerials($serial);
        $currentMarketingOptSerials = $this->getExistingMarketingOptSerials($profileId, $serials);
        $currentLocationOptSerials = $this->getExistingLocationOptSerials($profileId, $serials);
        $this->createMissingOptOuts($profileId, $serials, $currentLocationOptSerials, $currentMarketingOptSerials);
        // set the correct values for the location and marketing opt outs
        $this->nearlyProfileOptOut->locationOpt($serials, $profileId, $optOutLocation);
        $this->nearlyProfileOptOut->marketingOptIn($serials, $profileId, $optOutMarketing);
        // and do the same on user registration
        $this->userRegistrationRepository->updateOptOuts($profileId, $serials, $optOutLocation, $optOutMarketing, $optOutMarketing);

        return Http::status(200);
    }

    /**
     * @param $serial
     * @return array
     */
    private function getSerials($serial): array
    {
        $serialSet = [$serial => true];

        // get all the serials in groups that this serial is in
        $groupSerials = $this->em->createQueryBuilder()
            ->select('lpgs1.serial')
            ->from(LocationPolicyGroupSerials::class, 'lpgs1')
            ->where($this->em->createQueryBuilder()->expr()->in(
                'lpgs1.groupId',
                $this->em->createQueryBuilder()
                    ->select('lpgs2.groupId')
                    ->from(LocationPolicyGroupSerials::class, 'lpgs2')
                    ->where('lpgs2.serial = :serial')
                    ->getDQL()))
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();
        // add them all to our set of serials to update
        foreach ($groupSerials as $groupSerial) {
            $serialSet[$groupSerial['serial']] = true;
        }
        $serials = array_keys($serialSet);

        return $serials;
    }

    /**
     * @param string $profileId
     * @param array $serials
     * @return array
     */
    private function getExistingMarketingOptSerials(string $profileId, array $serials): array
    {
// get the existing marketing opt outs
        $marketingCurrentUserOpts       = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(MarketingOptOut::class, 'u')
            ->where('u.uid = :profile')
            ->andWhere('u.serial IN (:serial)')
            ->setParameter('profile', $profileId)
            ->setParameter('serial', $serials)
            ->getQuery()
            ->getArrayResult();
        $marketingCurrentUserOptSerials = [];
        foreach ($marketingCurrentUserOpts as $marketingCurrentUserOpt) {
            $marketingCurrentUserOptSerials[$marketingCurrentUserOpt['serial']] = true;
        }

        return $marketingCurrentUserOptSerials;
    }

    /**
     * @param string $profileId
     * @param array $serials
     * @return array
     */
    private function getExistingLocationOptSerials(string $profileId, array $serials): array
    {
// get the existing location opt outs
        $locationsCurrentUserOpts       = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationOptOut::class, 'u')
            ->where('u.profileId = :uid')
            ->andWhere('u.serial IN (:serial)')
            ->setParameter('uid', $profileId)
            ->setParameter('serial', $serials)
            ->getQuery()
            ->getArrayResult();
        $locationsCurrentUserOptSerials = [];
        foreach ($locationsCurrentUserOpts as $locationsCurrentUserOpt) {
            $locationsCurrentUserOptSerials[$locationsCurrentUserOpt['serial']] = true;
        }
        return $locationsCurrentUserOptSerials;
    }

    /**
     * @param string $profileId
     * @param array $serials
     * @param $currentLocationOptSerials
     * @param array $currentMarketingOptSerials
     * @throws \Doctrine\ORM\ORMException
     */
    private function createMissingOptOuts(string $profileId, array $serials, $currentLocationOptSerials, array $currentMarketingOptSerials): void
    {
        foreach ($serials as $serial) {
            if (!array_key_exists($serial, $currentMarketingOptSerials)) {
                $newOptedOutSMS = new MarketingOptOut($profileId, $serial, 'sms');
                $this->em->persist($newOptedOutSMS);

                $newOptedOutEmail = new MarketingOptOut($profileId, $serial, 'email');
                $this->em->persist($newOptedOutEmail);
            }
            if (!array_key_exists($serial, $currentLocationOptSerials)) {
                $newOptedOutLocation          = new LocationOptOut($profileId, $serial);
                $newOptedOutLocation->deleted = true;
                $this->em->persist($newOptedOutLocation);
            }
        }
        // this code needs to call flush as it relies on the objects above existing
        $this->em->flush();
    }
}