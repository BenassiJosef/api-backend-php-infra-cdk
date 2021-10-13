<?php
/**
 * Created by jamieaitken on 05/11/2018 at 14:44
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports;

use App\Models\Nearly\ImpressionsAggregate;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class SplashScreenImpressions extends ReportController implements IReport
{

    private $defaultOrder = 'n.impressions';

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {

        $sql = "
            SUM(n.impressions) as impressions,
            SUM(n.converted) as converted,
            UNIX_TIMESTAMP(n.formattedTimestamp) as timestamp
        ";

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', n.year as year, n.month as month, n.day as day, n.hour as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', n.year as year, n.month as month, n.day as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(n.timestamp) as year, n.week as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', n.year as year, n.month as month';
            $group = 'year, month';
        }

        $startHour = intval($start->format('H'));
        $endHour = intval($end->format('H'));

        $startDay = intval($start->format('j'));
        $endDay = intval($end->format('j'));

        $startWeek = intval($start->format('W'));
        $endWeek = intval($end->format('W'));

        $startMonth = intval($start->format('m'));
        $endMonth = intval($end->format('m'));

        $startYear = intval($start->format('Y'));
        $endYear = intval($start->format('Y'));

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(ImpressionsAggregate::class, 'n')
            ->where('n.serial IN (:serial)')
            ->andWhere('n.hour BETWEEN :startHour AND :endHour')
            ->andWhere('n.day BETWEEN :startDay AND :endDay')
            ->andWhere('n.week BETWEEN :startWeek AND :endWeek')
            ->andWhere('n.month BETWEEN :startMonth AND :endMonth')
            ->andWhere('n.year BETWEEN :startYear AND :endYear')
            ->setParameter('serial', $serial)
            ->setParameter('startHour', $startHour)
            ->setParameter('endHour', $endHour)
            ->setParameter('startDay', $startDay)
            ->setParameter('endDay', $endDay)
            ->setParameter('startWeek', $startWeek)
            ->setParameter('endWeek', $endWeek)
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth', $endMonth)
            ->setParameter('startYear', $startYear)
            ->setParameter('endYear', $endYear)
            ->groupBy($group)
            ->getQuery()
            ->getArrayResult();

        if (empty($totals)) {
            return [];
        }

        foreach ($totals as $key => $value) {
            foreach ($value as $k => $v) {
                if (is_numeric($v)) {
                    $totals[$key][$k] = (int)round($v);
                }
            }
        }

        return $totals;
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $totals = $this->em->createQueryBuilder()
            ->select('SUM(u.impressions) as impressions, SUM(u.converted) as converted')
            ->from(ImpressionsAggregate::class, 'u')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.formattedTimestamp BETWEEN :startHour AND :endHour')
            ->setParameter('serial', $serial)
            ->setParameter('startHour', $start)
            ->setParameter('endHour', $end)
            ->getQuery()
            ->getArrayResult();

        if (empty($totals)) {
            return [];
        }

        foreach ($totals as $key => $value) {
            foreach ($value as $k => $v) {
                if (is_numeric($v)) {
                    $totals[0][$k] = (int)round($v);
                }
            }
        }

        return $totals[0];
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $options)
    {
        $this->defaultOrder = isset($options['order']) ? $options['order'] : '';
    }
}