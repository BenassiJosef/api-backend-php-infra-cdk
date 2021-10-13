<?php
/**
 * Created by jamieaitken on 11/05/2018 at 10:23
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;

use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use Doctrine\ORM\EntityManager;

class NearlyProfileOptOut
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function locationOpt(array $serial, string $profileId, bool $status)
    {
        $locationStatus  = false;
        $marketingStatus = true;

        if ($status === false) {
            $locationStatus  = true;
            $marketingStatus = false;
        }


        $this->em->createQueryBuilder()
            ->update(LocationOptOut::class, 'd')
            ->set('d.deleted', ':deleted')
            ->where('d.profileId = :id')
            ->andWhere('d.serial IN (:serial)')
            ->setParameter('deleted', $locationStatus)
            ->setParameter('id', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->update(MarketingOptOut::class, 'u')
            ->set('u.optOut', ':false')
            ->where('u.uid = :uid')
            ->andWhere('u.serial IN (:serial)')
            ->setParameter('false', $marketingStatus)
            ->setParameter('uid', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
    }

    public function marketingOptIn(array $serial, string $profileId, $status)
    {
        $this->em->createQueryBuilder()
            ->update(MarketingOptOut::class, 'u')
            ->set('u.optOut', ':status')
            ->where('u.uid = :uid')
            ->andWhere('u.serial IN (:serial)')
            ->setParameter('status', $status)
            ->setParameter('uid', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
    }
}