<?php
/**
 * Created by jamieaitken on 17/05/2018 at 11:37
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports;


use App\Models\Marketing\MarketingOptOut;
use App\Models\UserData;
use App\Models\UserRegistration;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MarketingOptOutReportController extends ReportController implements IReport
{
    private $defaultOrder = 'u.updatedAt';
    private $exportHeaders = [
        'Profile ID',
        'Email',
        'Has Opted Out',
        'Type',
        'Updated At'
    ];

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $maxResults = 50;
        $optOut     = $this->em->createQueryBuilder()
            ->select('u.uid, ud.email, u.optOut, u.type, u.updatedAt')
            ->from(MarketingOptOut::class, 'u')
            ->leftJoin(UserRegistration::class, 'ur', 'WITH', 'u.uid = ur.profileId')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.updatedAt BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->groupBy('u.uid')
            ->setFirstResult($options['offset'])
            ->setMaxResults($maxResults);

        $results = new Paginator($optOut);
        $results->setUseOutputWalkers(false);

        $optOut = $results->getIterator()->getArrayCopy();

        if (empty($optOut)) {
            return [];
        }

        $return = [
            'table'       => $optOut,
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $options['offset'] + $maxResults
        ];

        if ($options['offset'] <= $return['total'] && count($optOut) !== $return['total']) {
            $return['has_more'] = true;
        }

        return $return;
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $sql = '
        UNIX_TIMESTAMP(u.updatedAt) as timestamp,
        COUNT(DISTINCT (u.uid)) as optOuts
        ';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(u.updatedAt) as year, MONTH(u.updatedAt) as month, DAY(u.updatedAt) as day, HOUR(u.updatedAt) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(u.updatedAt) as year, MONTH(u.updatedAt) as month, DAY(u.updatedAt) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(u.updatedAt) as year, WEEK(u.updatedAt) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(u.updatedAt) as year, MONTH(u.updatedAt) as month';
            $group = 'year, month';
        }

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(MarketingOptOut::class, 'u')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.updatedAt BETWEEN :now AND :end')
            ->andWhere('u.optOut = :true')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->setParameter('true', true)
            ->groupBy($group)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (empty($totals)) {
            return [];
        }

        foreach ($totals as $key => $optOut) {
            foreach ($optOut as $k => $v) {
                if (is_numeric($v)) {
                    $totals[$key][$k] = (int)$v;
                }
            }
        }

        return $totals;
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $optOutSms = $this->em->createQueryBuilder()
            ->select('
                COUNT(DISTINCT u.uid) as optOuts
            ')
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(MarketingOptOut::class, 'u', 'WITH', 'u.uid = ur.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.lastSeenAt BETWEEN :now AND :end')
            ->andWhere('u.optOut = :true')
            ->andWhere('u.type = :sms')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->setParameter('true', true)
            ->setParameter('sms', 'sms')
            ->getQuery()
            ->getArrayResult();

        $optOutEmail = $this->em->createQueryBuilder()
            ->select('
                COUNT(DISTINCT u.uid) as optOuts
            ')
            ->from(UserRegistration::class, 'ur')
            ->leftJoin(MarketingOptOut::class, 'u', 'WITH', 'u.uid = ur.profileId')
            ->where('ur.serial IN (:serial)')
            ->andWhere('ur.lastSeenAt BETWEEN :now AND :end')
            ->andWhere('u.optOut = :true')
            ->andWhere('u.type = :email')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->setParameter('true', true)
            ->setParameter('email', 'email')
            ->getQuery()
            ->getArrayResult();

        return [
            'optOutSms'    => (int)$optOutSms[0]['optOuts'],
            'optOutEmails' => (int)$optOutEmail[0]['optOuts']
        ];
    }

    public function export(array $serial, \DateTime $start, \DateTime $end, array $options)
    {
        $optOut = $this->em->createQueryBuilder()
            ->select('u.uid, ud.email, u.optOut, u.type, u.updatedAt')
            ->from(MarketingOptOut::class, 'u')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'u.uid = ud.profileId')
            ->where('u.serial IN (:serial)')
            ->andWhere('u.updatedAt BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->groupBy('u.uid');

        $results = new Paginator($optOut);
        $results->setUseOutputWalkers(false);

        $optOut = $results->getIterator()->getArrayCopy();

        if (empty($optOut)) {
            return [];
        }


        foreach ($optOut as $key => $item) {
            foreach ($item as $k => $v) {
                if ($k === 'email' && (empty($v) || is_null($v))) {
                    $optOut[$key][$k] = 'EMAIL_NOT_GIVEN';
                }

                if ($k === 'optOut') {
                    if ($v) {
                        $optOut[$key][$k] = 'Opted out';
                    } else {
                        $optOut[$key][$k] = 'Not Opted out';
                    }
                }
            }
        }


        return $optOut;
    }

    public function getExportHeaders()
    {
        return $this->exportHeaders;
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $options)
    {
        if (array_key_exists('order', $options) && !is_null($options['order'])) {
            $this->defaultOrder = $options['order'];
        }
    }
}