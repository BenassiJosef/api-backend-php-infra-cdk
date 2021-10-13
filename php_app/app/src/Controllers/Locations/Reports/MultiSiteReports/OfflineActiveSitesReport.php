<?php
/**
 * Created by jamieaitken on 14/06/2018 at 12:57
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use Doctrine\ORM\EntityManager;

class OfflineActiveSitesReport implements IMultiSiteReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getData(array $serial, array $options)
    {
        $anHour = new \DateTime();
        $anHour->modify('-1 hour');

        return $this->em->createQueryBuilder()
            ->select('ls.serial, ls.alias, u.timestamp')
            ->from(Inform::class, 'u')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'u.serial = ls.serial')
            ->leftJoin(Subscriptions::class, 's', 'WITH', 'ls.serial = s.serial')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.timestamp <= :anHour')
            ->andWhere('u.status = :false')
            ->andWhere('s.status = :active')
            ->setParameter('serial', $serial)
            ->setParameter('anHour', $anHour)
            ->setParameter('false', false)
            ->setParameter('active', 'active')
            ->groupBy('ls.serial')
            ->orderBy('ls.alias', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}