<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 12:57
 */

namespace App\Controllers\Locations\Reports;

use App\Models\Device\DeviceBrowser;
use App\Models\Device\DeviceOs;
use App\Models\User\UserAgent;
use App\Models\User\UserDevice;
use App\Models\UserData;

class _DeviceReportController extends ReportController implements IReport
{
    private $defaultType = 'deviceModel';

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $maxResults = 50;
        $devices    = $this->em->createQueryBuilder()
            ->select('
            ud.type as deviceType, 
            ud.brand as deviceBrand,
            ud.model as deviceModel,
            COALESCE(SUM(n.dataDown),0) as dataDown, 
            COALESCE(SUM(n.dataUp),0) as dataUp,
            COALESCE(AVG(n.dataDown),0) as avgDataDown,
            COALESCE(AVG(n.dataUp),0) as avgDataUp,
            SUM(TIMESTAMPDIFF(SECOND, n.timestamp, n.lastupdate)) AS uptime,
            AVG(TIMESTAMPDIFF(SECOND, n.timestamp, n.lastupdate)) AS avgUptime,
            COALESCE(SUM(n.authTime),0) as authTime,
            COALESCE(AVG(n.authTime),0) as avgAuthTime,
            COUNT(DISTINCT n.mac) as unique')
            ->from(UserData::class, 'n')
            ->leftJoin(UserDevice::class, 'ud', 'WITH', 'n.mac = ud.mac')
            ->where('n.serial IN (:serial)')
            ->andWhere('n.dataUp IS NOT NULL')
            ->andWhere(':type IS NOT NULL')
            ->andWhere(':type != :unk')
            ->andWhere('n.timestamp BETWEEN :now AND :then')
            ->setParameter('serial', $serial)
            ->setParameter('unk', 'unknown')
            ->setParameter('now', $start)
            ->setParameter('then', $end)
            ->setParameter('type', $this->defaultType)
            ->groupBy($this->defaultType)
            ->orderBy('dataDown', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (!empty($devices)) {
            foreach ($devices as $key => $device) {
                foreach ($device as $k => $v) {
                    if (is_numeric($v)) {
                        $devices[$key][$k] = (int)round($v);
                    }
                }
            }

            $return = [
                'table'       => $devices,
                'has_more'    => false,
                'total'       => count($devices),
                'next_offset' => $options['offset'] + $maxResults
            ];

            return $return;
        }

        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $sql = '
        COALESCE(SUM(n.dataDown),0) as dataDown, 
        COALESCE(SUM(n.dataUp),0) as dataUp, 
        ud.type as deviceType, 
        ud.brand as deviceBrand,
        ud.model as deviceModel,
        db.type as browserType,
        db.name as browserName,
        db.version as browserVersion, 
        do.name as osName,
        do.version as osVersion, 
        SUM(TIMESTAMPDIFF(SECOND, n.timestamp, n.lastupdate)) AS uptime,
        COALESCE(SUM(n.authTime),0) as authTime, 
        COALESCE(COUNT(DISTINCT(n.mac)),0) as unique, 
        UNIX_TIMESTAMP(n.timestamp) as timestamp';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(n.timestamp) as year, MONTH(n.timestamp) as month, DAY(n.timestamp) as day, HOUR(n.timestamp) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(n.timestamp) as year, MONTH(n.timestamp) as month, DAY(n.timestamp) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(n.timestamp) as year, WEEK(n.timestamp) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(n.timestamp) as year, MONTH(n.timestamp) as month';
            $group = 'year, month';
        }

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'n')
            ->leftJoin(UserDevice::class, 'ud', 'WITH', 'n.mac = ud.mac')
            ->leftJoin(UserAgent::class, 'ua', 'WITH', 'ud.id = ua.userDeviceId')
            ->leftJoin(DeviceBrowser::class, 'db', 'WITH', 'ua.deviceBrowserId = db.id')
            ->leftJoin(DeviceOs::class, 'do', 'WITH', 'ua.deviceOsId = do.id')
            ->where('n.serial IN (:serial)')
            ->andWhere('n.dataUp IS NOT NULL')
            ->andWhere(':type IS NOT NULL')
            ->andWhere(':type != :unk')
            ->andWhere('n.timestamp BETWEEN :start AND :finish')
            ->setParameter('serial', $serial)
            ->setParameter('unk', 'unknown')
            ->setParameter('start', $start)
            ->setParameter('finish', $end)
            ->setParameter('type', $this->defaultType)
            ->groupBy($group)
            ->orderBy('dataDown', 'DESC')
            ->getQuery()
            ->getArrayResult();
        if (!empty($totals)) {
            foreach ($totals as $key => $value) {
                foreach ($value as $k => $v) {
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
        COALESCE(SUM(n.dataDown),0) as dataDown, 
        COALESCE(SUM(n.dataUp),0) as dataUp,
        COALESCE(AVG(n.dataDown),0) as avgDataDown,
        COALESCE(AVG(n.dataUp),0) as avgDataUp,
        ud.type as deviceType, 
        ud.brand as deviceBrand,
        ud.model as deviceModel,
        SUM(TIMESTAMPDIFF(SECOND, n.timestamp, n.lastupdate)) AS uptime,
        AVG(TIMESTAMPDIFF(SECOND, n.timestamp, n.lastupdate)) AS avgUptime,
        COALESCE(SUM(n.authTime),0) as authTime,
        COALESCE(AVG(n.authTime),0) as avgAuthTime,
        COALESCE(COUNT(DISTINCT n.mac),0) as unique';

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'n')
            ->leftJoin(UserDevice::class, 'ud', 'WITH', 'n.mac = ud.mac')
            ->where('n.serial IN (:serial)')
            ->andWhere('n.dataUp IS NOT NULL')
            ->andWhere('n.timestamp BETWEEN :start AND :finish')
            ->andWhere(':type IS NOT NULL')
            ->andWhere(':type != :unk')
            ->setParameter('serial', $serial)
            ->setParameter('unk', 'unknown')
            ->setParameter('start', $start)
            ->setParameter('finish', $end)
            ->setParameter('type', $this->defaultType)
            ->groupBy($this->defaultType)
            ->orderBy('dataDown', 'DESC')
            ->getQuery()
            ->getArrayResult();
        if (!empty($totals)) {
            foreach ($totals as $key => $value) {
                foreach ($value as $k => $v) {
                    if (is_numeric($v)) {
                        $totals[0][$k] = (int)round($v);
                    }
                }
            }

            return $totals[0];
        }

        return [];
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultType;
    }

    public function setDefaultOrder(array $options)
    {
        if (array_key_exists('order', $options) && !is_null($options['order'])) {
            $this->defaultType = $options['order'];
        }
    }
}
