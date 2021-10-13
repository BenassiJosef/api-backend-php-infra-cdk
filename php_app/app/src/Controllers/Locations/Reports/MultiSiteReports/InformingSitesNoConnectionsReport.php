<?php
/**
 * Created by jamieaitken on 14/06/2018 at 13:01
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\UserData;
use Doctrine\ORM\EntityManager;

class InformingSitesNoConnectionsReport implements IMultiSiteReport
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
            ->leftJoin(UserData::class, 'ud', 'WITH', 's.serial = ud.serial')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.timestamp <= :anHour')
            ->andWhere('u.status = :true')
            ->andWhere('ud.serial IS NULL')
            ->andWhere('s.status = :active')
            ->setParameter('serial', $serial)
            ->setParameter('anHour', $anHour)
            ->setParameter('true', true)
            ->setParameter('active', 'active')
            ->orderBy('ls.alias', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}