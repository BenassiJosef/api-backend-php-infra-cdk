<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 10:55
 */

namespace App\Controllers\Locations\Reports;

class _ReportChartGenerator
{
    public function __construct()
    {
    }

    public function getClosestTimeStamp(int $search, array $array)
    {
        $closest = null;
        foreach ($array as $index => $item) {
            if ($closest === null || abs($search - $closest) > abs($index - $search)) {
                $closest = $index;
            }
        }

        return $closest;
    }

    public function generateGrouping(\DateTime $start, \DateTime $end)
    {

        $startReference = new \DateTime();

        $startReference->setTimestamp($start->getTimestamp());

        $differenceBetweenTwo = $startReference->diff($end);
        $difference           = 0;
        $grouping             = null;
        if ($differenceBetweenTwo->y > 0) {
            $difference = $differenceBetweenTwo->y * 13 + $differenceBetweenTwo->m;
            $grouping   = 'months';
            $modify     = 'first day of this month 00:00:00';
        } elseif ($differenceBetweenTwo->m >= 3) {
            $difference = $differenceBetweenTwo->days / 7;
            $grouping   = 'months';
            $modify     = 'first day of this month 00:00:00';
        } elseif ($differenceBetweenTwo->m > 0) {
            $difference = $differenceBetweenTwo->days;
            $grouping   = 'days';
            $modify     = 'today';
        } elseif ($differenceBetweenTwo->d > 3) {
            $difference = $differenceBetweenTwo->days;
            $grouping   = 'days';
            $modify     = 'today';
        } elseif ($differenceBetweenTwo->days > 0) {
            $difference = $differenceBetweenTwo->h + ($differenceBetweenTwo->d * 24);
            $grouping   = 'hours';
            $modify     = 'first hour this day';
        } elseif ($differenceBetweenTwo->h > 0) {
            $difference = $differenceBetweenTwo->h;
            $grouping   = 'hours';
            $modify     = 'first hour this day';
        } elseif ($differenceBetweenTwo->i > 0) {
            $difference = $differenceBetweenTwo->i * 60;
            $grouping   = 'minutes';
            $modify     = 'H:i:00';
        }

        $dataStructure = [];
        for ($i = 0; $i < $difference + 1; $i++) {
            $startReferenceLoop = new \DateTime();
            $startReferenceLoop->setTimestamp($start->getTimestamp());
            $startReferenceLoop->modify($modify);
            $startTime                                       = $startReferenceLoop->modify('+' . $i . ' ' . $grouping);
            $dataStructure['chart'][$startTime->format('U')] = [];
        }

        $dataStructure['group']  = $grouping;
        $dataStructure['modify'] = $modify;

        return $dataStructure;
    }

    public function addDataToGrouping(array $dataToGroup, array $dataStructure, string $timeKey)
    {
        $zeroValue = $dataToGroup[0];
        foreach ($zeroValue as $plotVal => $int) {
            $zeroValue[$plotVal] = 0;
        }

        foreach ($dataStructure['chart'] as $timestamp => $allDataValues) {
            $dataStructure['chart'][$timestamp] = $zeroValue;
        }

        foreach ($dataToGroup as $plotData) {
            $time          = new \DateTime();
            $beautifulTime = $time->setTimestamp($plotData[$timeKey])->modify($dataStructure['modify'])->format('U');
            $index         = $this->getClosestTimeStamp($beautifulTime, $dataStructure['chart']);
            if (isset($dataStructure['chart'][$index])) {
                $dataStructure['chart'][$index] = $plotData;
            }
        }

        return $dataStructure['chart'];
    }
}
