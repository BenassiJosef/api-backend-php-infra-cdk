<?php
/**
 * Created by jamieaitken on 26/04/2019 at 15:34
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Utils\CacheEngine;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class TopPerformingCustomersController implements IMultiSiteReport
{
    protected $em;
    protected $cache;

    public function __construct(EntityManager $em)
    {
        $this->em    = $em;
        $this->cache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function getData(array $serial, array $options)
    {

        $cacheFetch = $this->cache->fetch('masterReports:topPerformingCustomers');

        if (!is_bool($cacheFetch)) {
            return $cacheFetch;
        }

        $oneMonth = new \DateTime();
        $oneMonth->modify('- 1 months');

        $now = new \DateTime();

        $returnStructure = [];

        $newCustomers = $this->em->createQueryBuilder()
            ->select('DISTINCT o.email, o.uid, COUNT(DISTINCT up.id) as newCustomers, ud.serial')
            ->from(UserProfile::class, 'up')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId AND ud.serial IN (:serial)')
            ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'ud.serial = na.serial')
            ->leftJoin(OauthUser::class, 'o', 'WITH', 'na.admin = o.uid')
            ->where('up.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $oneMonth)
            ->setParameter('end', $now)
            ->setParameter('serial', $serial)
            ->groupBy('ud.serial')
            ->orderBy('newCustomers', 'DESC')
            ->getQuery()
            ->getArrayResult();


        $customerEmailArray  = [];
        $customerSerialArray = [];

        foreach ($newCustomers as $customer) {

            if (sizeof($customerEmailArray) === 20) {
                continue;
            }

            if (!isset($returnStructure[$customer['email']])) {

                $customerEmailArray[] = $customer['email'];

                $returnStructure[$customer['email']] = [
                    'email'          => $customer['email'],
                    'uid'            => $customer['uid'],
                    'totalCustomers' => 0,
                    'newCustomers'   => 0,
                    'verified'       => 0
                ];
            }

            $customerSerialArray[] = $customer['serial'];

            $returnStructure[$customer['email']]['newCustomers'] += $customer['newCustomers'];
        }

        $verified = $this->em->createQueryBuilder()
            ->select('COUNT(DISTINCT up.id) as verified, o.email')
            ->from(UserProfile::class, 'up')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId AND ud.serial IN (:serial)')
            ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'ud.serial = na.serial')
            ->leftJoin(OauthUser::class, 'o', 'WITH', 'na.admin = o.uid')
            ->where('ud.dataUp IS NOT NULL')
            ->andWhere('up.timestamp BETWEEN :start AND :end')
            ->andWhere('up.verified = 1')
            ->setParameter('serial', $customerSerialArray)
            ->setParameter('start', $oneMonth)
            ->setParameter('end', $now)
            ->groupBy('o.email')
            ->getQuery()
            ->getArrayResult();

        foreach ($verified as $customerVerifications) {
            $returnStructure[$customerVerifications['email']]['verified'] = $customerVerifications['verified'];
        }

        $totalCustomers = $this->em->createQueryBuilder()
            ->select('o.email, COUNT(DISTINCT ud.profileId) as totalUsers')
            ->from(UserProfile::class, 'up')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId AND ud.serial IN (:serial)')
            ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'ud.serial = na.serial')
            ->leftJoin(OauthUser::class, 'o', 'WITH', 'na.admin = o.uid')
            ->where('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $oneMonth)
            ->setParameter('end', $now)
            ->setParameter('serial', $customerSerialArray)
            ->groupBy('o.email')
            ->getQuery()
            ->getArrayResult();

        foreach ($totalCustomers as $customer) {
            $returnStructure[$customer['email']]['totalCustomers'] += $customer['totalUsers'];
        }

        $this->cache->save('masterReports:topPerformingCustomers', $returnStructure);

        return $returnStructure;
    }
}