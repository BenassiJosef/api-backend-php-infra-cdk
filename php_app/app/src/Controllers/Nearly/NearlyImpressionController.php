<?php
/**
 * Created by jamieaitken on 30/10/2018 at 17:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;

use App\Controllers\Locations\Reports\MultiSiteReports\IMultiSiteReport;
use App\Models\Locations\LocationSettings;
use App\Models\Nearly\Impressions;
use App\Models\Nearly\ImpressionsAggregate;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class NearlyImpressionController implements IMultiSiteReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getData(array $serial, array $options)
    {
        $dateStart = new \DateTime();
        $dateEnd   = new \DateTime();

        if (isset($options['start'])) {
            $dateStart->setTimestamp($options['start']);
        } else {
            $dateStart->modify('- 3months');
        }

        if (isset($options['end'])) {
            $dateEnd->setTimestamp($options['end']);
        }


        $getImpressionTotals = $this->em->createQueryBuilder()
            ->select('
                                   SUM(u.impressions) as totalImpressions, 
                                   SUM(u.converted) as totalConversions,
                                   u.serial,
                                   ls.alias')
            ->from(ImpressionsAggregate::class, 'u')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'u.serial = ls.serial')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.formattedTimestamp BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd);
        if (isset($options['group'])) {
            $getImpressionTotals = $getImpressionTotals->groupBy('u.serial');
        }

        $getImpressionTotals = $getImpressionTotals->getQuery()
            ->getArrayResult();


        foreach ($getImpressionTotals as $key => $impressionTotal) {
            $getImpressionTotals[$key]['conversionRate'] = 0;
            if ($impressionTotal['totalConversions'] > 0) {
                $getImpressionTotals[$key]['conversionRate'] = round(($impressionTotal['totalConversions'] / $impressionTotal['totalImpressions']) * 100,
                    2);
            }
        }

        if (!isset($options['group'])) {
            return $getImpressionTotals[0];
        }

        return $getImpressionTotals;

    }
}