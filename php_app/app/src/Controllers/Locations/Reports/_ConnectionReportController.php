<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 11:37
 */

namespace App\Controllers\Locations\Reports;

use App\Models\UserData;
use App\Models\UserProfile;
use Doctrine\ORM\Tools\Pagination\Paginator;

class _ConnectionReportController extends ReportController implements IReport
{
    private $defaultOrder = 'ud.timestamp';
    private $exportHeaders = [
        'Id',
        'Email',
        'Total Uploaded',
        'Total Downloaded',
        'Up Time',
        'Last Connected',
        'Timestamp'
    ];

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $sql = '
        up.id, 
        up.email, 
up.first,
up.last,
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime, 
        ud.lastupdate,
        ud.timestamp';

        $maxResults     = 50;
        $connectedUsers = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->join(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->andWhere('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('ud.id')
            ->orderBy($this->defaultOrder, $options['sort']);
        if ($options['export'] === false) {
            $connectedUsers
                ->setFirstResult($options['offset'])
                ->setMaxResults($maxResults);
        }

        $results = new Paginator($connectedUsers);
        $results->setUseOutputWalkers(false);

        $connectedUsers = $results->getIterator()->getArrayCopy();

        if (!empty($connectedUsers)) {
            foreach ($connectedUsers as $key => $connection) {
                foreach ($connection as $k => $v) {
                    if (is_numeric($v)) {
                        $connectedUsers[$key][$k] = (int)round($v);
                    }
                }
            }

            $return = [
                'table'       => $connectedUsers,
                'has_more'    => false,
                'total'       => count($results),
                'next_offset' => $options['offset'] + $maxResults
            ];

            if ($options['offset'] <= $return['total'] && count($connectedUsers) !== $return['total']) {
                $return['has_more'] = true;
            }

            return $return;
        }

        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $sql = '
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections, 
        COUNT(DISTINCT(ud.profileId)) as uniqueConnections,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
        COALESCE(AVG(ud.dataUp),0) as averageUp,
        COALESCE(AVG(ud.dataDown),0) as averageDown, 
        AVG(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as averageUptime,
        UNIX_TIMESTAMP(ud.timestamp) as timestamp';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day, HOUR(ud.timestamp) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(ud.timestamp) as year, WEEK(ud.timestamp) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month';
            $group = 'year, month';
        }

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->andWhere('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('serial', $serial)
            ->groupBy($group)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($totals)) {
            foreach ($totals as $key => $connection) {
                foreach ($connection as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[$key][$k] = (int)round($v);
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
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections, 
        COUNT(DISTINCT(ud.profileId)) as uniqueConnections,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
        AVG(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as averageUpTime,
        COALESCE(AVG(ud.dataUp),0) as averageUp,
        COALESCE(AVG(ud.dataDown),0) as averageDown';

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->andWhere('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('serial', $serial)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($totals)) {
            foreach ($totals as $key => $connection) {
                foreach ($connection as $k => $v) {
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
        $sql = '
        up.id, 
        up.email, 
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime, 
        ud.lastupdate,
        ud.timestamp';

        $connectedUsers = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->join(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->andWhere('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('serial', $serial)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('ud.id')
            ->orderBy($this->defaultOrder, $options['sort']);

        $results = new Paginator($connectedUsers);
        $results->setUseOutputWalkers(false);

        $connectedUsers = $results->getIterator()->getArrayCopy();

        if (!empty($connectedUsers)) {
            foreach ($connectedUsers as $key => $connection) {
                foreach ($connection as $k => $v) {
                    if (is_numeric($v)) {
                        $connectedUsers[$key][$k] = (int)round($v);
                    }
                }
            }
        }

        return $connectedUsers;
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
