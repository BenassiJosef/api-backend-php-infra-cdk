<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:32
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


use App\Utils\Formatters\MemoryUnitFormatter;
use App\Utils\Formatters\TimeUnitFormatter;

class NearlyProfileCSVLocationConnections extends NearlyProfileCSVComponent
{

    public function create()
    {
        $newArray = [];
        foreach ($this->contents as $key => $connection) {
            foreach ($connection['connections'] as $k => $v) {
                $connectedAt = new \DateTime($v['connectedAt']);
                if (!is_null($v['lastseenAt'])) {
                    $lastSeenAt = new \DateTime($v['lastseenAt']);
                    $lastSeenAt = $lastSeenAt->format('g:ia \o\n l jS F Y');
                } else {
                    $lastSeenAt = 0;
                }
                $newArray[] = [
                    $connection['name'],
                    MemoryUnitFormatter::format($v['totalDownload']),
                    MemoryUnitFormatter::format($v['totalUpload']),
                    TimeUnitFormatter::format($v['uptime']),
                    $connectedAt->format('g:ia \o\n l jS F Y'),
                    $lastSeenAt
                ];
            }
        }
        $this->contents = $newArray;
    }
}