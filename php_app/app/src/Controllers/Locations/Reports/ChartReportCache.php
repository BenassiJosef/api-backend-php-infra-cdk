<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/06/2017
 * Time: 11:32
 */

namespace App\Controllers\Locations\Reports;

use App\Utils\CacheEngine;

class ChartReportCache
{
    protected $cache;

    public function __construct(CacheEngine $cacheEngine)
    {
        $this->cache = $cacheEngine;
    }

    public function getOrSaveToCache(array $serial, string $kind, string $order, int $dateTimeStart, int $dateTimeEnd)
    {

        $now          = new \DateTime();
        $nowTimestamp = $now->getTimestamp();

        $fetch = $this->cache->fetch('reports:' . implode(',', $serial) . ':'
            . $kind . ':' . $order . ':' . $dateTimeStart . '_' . $dateTimeEnd);

        if ($fetch === false) {
            return false;
        }

        $maxTimestamp = 0;

        foreach ($fetch as $key => $item) {
            if ($item['timestamp'] > $maxTimestamp) {
                $maxTimestamp = $item['timestamp'];
            }
        }

        if ($nowTimestamp > $maxTimestamp) {
            return $fetch;
        }

        return false;
    }
}
