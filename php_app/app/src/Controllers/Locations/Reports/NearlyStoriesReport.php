<?php
/**
 * Created by jamieaitken on 07/03/2019 at 10:32
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports;


use App\Models\Nearly\Stories\NearlyStoryPageActivityAggregate;

class NearlyStoriesReport extends ReportController implements IReport
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
        SUM(n.clicks) as clicks,
        SUM(n.conversions) as conversions,
        UNIX_TIMESTAMP(n.formattedTimestamp) as timestamp";

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(n.formattedTimestamp) as year, MONTH(n.formattedTimestamp) as month, DAY(n.formattedTimestamp) as day, HOUR(n.formattedTimestamp) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(n.formattedTimestamp) as year, MONTH(n.formattedTimestamp) as month, DAY(n.formattedTimestamp) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(n.formattedTimestamp) as year, WEEK(n.formattedTimestamp) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(n.formattedTimestamp) as year, MONTH(n.formattedTimestamp) as month';
            $group = 'year, month';
        }


        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(NearlyStoryPageActivityAggregate::class, 'n')
            ->where('n.serial IN (:serial)')
            ->andWhere('n.formattedTimestamp BETWEEN :startHour AND :endHour')
            ->andWhere('n.isArchived = :false');
        if (isset($options['pageId'])) {
            $totals = $totals->andWhere('n.pageId = :pageId')
                ->setParameter('pageId', $options['pageId']);
        }
        $totals = $totals->setParameter('serial', $serial)
            ->setParameter('startHour', $start)
            ->setParameter('endHour', $end)
            ->setParameter('false', false)
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
                } else if (is_null($v)) {
                    $totals[$key][$k] = 0;
                }

            }
        }

        return $totals;
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $totals = $this->em->createQueryBuilder()
            ->select('SUM(u.impressions) as impressions, SUM(u.conversions) as conversions, SUM(u.clicks) as clicks')
            ->from(NearlyStoryPageActivityAggregate::class, 'u')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.formattedTimestamp BETWEEN :startHour AND :endHour')
            ->andWhere('u.isArchived = :false');
        if (isset($options['pageId'])) {
            $totals = $totals->andWhere('u.pageId = :pageId')
                ->setParameter('pageId', $options['pageId']);
        }
        $totals = $totals->setParameter('serial', $serial)
            ->setParameter('startHour', $start)
            ->setParameter('endHour', $end)
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        if (empty($totals)) {
            return [];
        }

        foreach ($totals as $key => $value) {
            foreach ($value as $k => $v) {
                if (is_numeric($v)) {
                    $totals[0][$k] = (int)round($v);
                } else if (is_null($v)) {
                    $totals[0][$k] = 0;
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