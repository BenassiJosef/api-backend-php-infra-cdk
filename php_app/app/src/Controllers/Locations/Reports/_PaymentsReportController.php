<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 10:49
 */

namespace App\Controllers\Locations\Reports;

use App\Models\UserPayments;
use App\Models\UserProfile;
use Doctrine\ORM\Tools\Pagination\Paginator;

class _PaymentsReportController extends ReportController implements IReport
{
    private $defaultOrder = 'upa.creationdate';
    private $exportHeaders = [
        'Id',
        'Transaction ID',
        'Creation Date',
        'Email',
        'Duration',
        'Payment Amount',
        'Status',
        'Serial'
    ];

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $maxResults = 50;
        $payments   = $this->em->createQueryBuilder()
            ->select('
            up.id, 
            up.first, 
            up.last,
            upa.transactionId, 
            upa.creationdate, 
            up.email, 
            upa.email as paymentEmail,
            upa.duration, 
            upa.paymentAmount,
            upa.status, 
            upa.serial')
            ->from(UserPayments::class, 'upa')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'upa.profileId = up.id')
            ->where('upa.serial IN (:serial)')
            ->andWhere('upa.creationdate BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->setFirstResult($options['offset'])
            ->setMaxResults($maxResults);


        $results = new Paginator($payments);
        $results->setUseOutputWalkers(false);

        $payments = $results->getIterator()->getArrayCopy();

        if (!empty($payments)) {
            foreach ($payments as $key => $payment) {
                foreach ($payment as $k => $v) {
                    if (is_numeric($v)) {
                        if ($k === 'paymentAmount') {
                            $v                  = $v / 100;
                            $payments[$key][$k] = (float)$v;
                        } else {
                            $payments[$key][$k] = (int)$v;
                        }
                    }
                }
            }

            $return = [
                'table'       => $payments,
                'has_more'    => false,
                'total'       => count($results),
                'next_offset' => $options['offset'] + $maxResults
            ];

            $this->em->flush();

            if ($options['offset'] <= $return['total'] && count($payments) !== $return['total']) {
                $return['has_more'] = true;
            }

            return $return;
        }

        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $sql = '
        UNIX_TIMESTAMP(upa.creationdate) as timestamp, 
        upa.creationdate, 
        COALESCE(SUM(upa.duration),0) as duration, 
        COALESCE(SUM(upa.paymentAmount),0) as amount, 
        COUNT(DISTINCT(upa.id)) as payments, 
        upa.status, 
        upa.serial';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(upa.creationdate) as year, MONTH(upa.creationdate) as month, DAY(upa.creationdate) as day, HOUR(upa.creationdate) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(upa.creationdate) as year, MONTH(upa.creationdate) as month, DAY(upa.creationdate) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(upa.creationdate) as year, WEEK(upa.creationdate) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(upa.creationdate) as year, MONTH(upa.creationdate) as month';
            $group = 'year, month';
        }
        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserPayments::class, 'upa')
            ->where('upa.serial IN (:serial)')
            ->andWhere('upa.creationdate BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->groupBy($group)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();


        if (!empty($totals)) {
            foreach ($totals as $key => $payment) {
                foreach ($payment as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[$key][$k] = (int)$v;
                    }
                }
            }

            return $totals;
        }

        return [];
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {

        $sql = '
        COALESCE(SUM(upa.duration),0) as duration,
        COALESCE(SUM(upa.paymentAmount),0) as amount,
        COALESCE(AVG(upa.duration),0) as avgDuration,
        COALESCE(AVG(upa.paymentAmount),0) as avgAmount,
        COUNT(DISTINCT(upa.id)) as payments,
        COUNT(DISTINCT upa.email) as repeatPayments';

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserPayments::class, 'upa')
            ->where('upa.serial IN (:serial)')
            ->andWhere('upa.creationdate BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();


        if (!empty($totals)) {
            foreach ($totals as $key => $payment) {
                foreach ($payment as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[0][$k] = (int)round($v);
                    }
                }
            }

            return $totals[0];
        }

        return [];
    }

    public function export(array $serial, \DateTime $start, \DateTime $end, array $options)
    {
        $payments = $this->em->createQueryBuilder()
            ->select('
            up.id, 
            upa.transactionId, 
            upa.creationdate, 
            upa.email, 
            upa.duration, 
            upa.paymentAmount,
            upa.status, 
            upa.serial')
            ->from(UserPayments::class, 'upa')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'upa.profileId = up.id')
            ->where('upa.serial IN (:serial)')
            ->andWhere('upa.creationdate BETWEEN :now AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort']);

        $results = new Paginator($payments);
        $results->setUseOutputWalkers(false);

        $payments = $results->getIterator()->getArrayCopy();

        if (!empty($payments)) {
            foreach ($payments as $key => $payment) {
                foreach ($payment as $k => $v) {
                    if (is_numeric($v)) {
                        if ($k === 'paymentAmount') {
                            $v                  = $v / 100;
                            $payments[$key][$k] = (float)$v;
                        } else {
                            $payments[$key][$k] = (int)$v;
                        }
                    }
                }
            }

            return $payments;
        }

        return [];
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
        if (isset($options['order'])) {
            $this->defaultOrder = $options['order'];
        }
    }
}
