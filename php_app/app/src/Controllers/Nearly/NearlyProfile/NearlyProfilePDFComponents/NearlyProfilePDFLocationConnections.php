<?php
/**
 * Created by jamieaitken on 14/05/2018 at 09:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;


use App\Utils\Formatters\MemoryUnitFormatter;
use App\Utils\Formatters\TimeUnitFormatter;

class NearlyProfilePDFLocationConnections extends NearlyProfilePDFComponent
{

    public function create()
    {
        $html = '';
        foreach ($this->contents as $key => $connection) {
            foreach ($connection['connections'] as $k => $v) {
                $connectedAt = new \DateTime($v['connectedAt']);
                if (!is_null($v['lastseenAt'])) {
                    $lastSeenAt = new \DateTime($v['lastseenAt']);
                    $lastSeenAt = $lastSeenAt->format('g:ia \o\n l jS F Y');
                } else {
                    $lastSeenAt = 0;
                }
                $html .= '<table>
                        <tr><td>Downloaded:</td><td>' . MemoryUnitFormatter::format($connection['totalDownload']) . '</td></tr>
                        <tr><td>Uploaded:</td><td>' . MemoryUnitFormatter::format($connection['totalUpload']) . '</td></tr>
                        <tr><td>Uptime:</td><td>' . TimeUnitFormatter::format($connection['uptime']) . '</td></tr>
                        <tr><td>Connected At:</td><td>' . $connectedAt->format('g:ia \o\n l jS F Y') . '</td></tr>
                        <tr><td>Last Seen At:</td><td>' . $lastSeenAt . '</td></tr>
                    </table>';
            }
        }

        return $html;
    }
}