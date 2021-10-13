<?php
/**
 * Created by jamieaitken on 03/05/2018 at 21:00
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;


use App\Utils\Formatters\MemoryUnitFormatter;

class NearlyProfilePDFDevices extends NearlyProfilePDFComponent
{
    public function create()
    {
        $html = '';
        foreach ($this->contents['devices'] as $key => $device) {
            $html .= "<table><tr><td>MAC Address:</td><td>" . $device['mac'] . "</td></tr>
                    <tr><td>Downloaded:</td><td>" . MemoryUnitFormatter::format($device['dataDown']) . "</td></tr>
                    <tr><td>Uploaded:</td><td>" . MemoryUnitFormatter::format($device['dataUp']) . "</td></tr>
                    <tr><td>Browser:</td><td>" . $device['name'] . "</td><td>" . $device['version'] . "</td></tr>
                    <tr><td>Device:</td><td>" . $device['brand'] . "</td><td>" . $device['model'] . "</td></tr></table>";
        }

        return $html;
    }
}