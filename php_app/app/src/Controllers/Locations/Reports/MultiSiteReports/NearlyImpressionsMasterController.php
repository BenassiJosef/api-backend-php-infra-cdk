<?php
/**
 * Created by jamieaitken on 07/11/2018 at 17:30
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;

use App\Models\Locations\LocationSettings;
use App\Models\Nearly\ImpressionsAggregate;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class NearlyImpressionsMasterController implements IMultiSiteReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getData(array $serial, array $options)
    {
        $startDatetime = new \DateTime('- 1day');
        $endDatetime   = new \DateTime();

        $aggregate = $this->em->createQueryBuilder()
            ->select(
                'u.serial, 
                SUM(u.impressions) as impressions,
                SUM(u.converted) as conversions,
                (SUM(u.converted)/SUM(u.impressions))*100 as percent,
                ls.alias')
            ->from(ImpressionsAggregate::class, 'u')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'u.serial = ls.serial')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.formattedTimestamp BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $startDatetime)
            ->setParameter('end', $endDatetime)
            ->orderBy('u.impressions', 'DESC')
            ->groupBy('u.serial')
            ->having('percent <= :threshold')
            ->setParameter('threshold', 30)
            ->getQuery()
            ->getArrayResult();

        if (empty($aggregate)) {
            return [];
        }

        foreach ($aggregate as $key => $location) {
            $aggregate[$key]['impressions'] = intval($location['impressions']);
            $aggregate[$key]['conversions'] = intval($location['conversions']);
            $aggregate[$key]['percent']     = floatval($location['percent']);
        }

        return $aggregate;

    }
}