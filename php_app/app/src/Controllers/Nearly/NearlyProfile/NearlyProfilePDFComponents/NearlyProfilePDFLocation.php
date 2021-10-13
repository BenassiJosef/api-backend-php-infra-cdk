<?php
/**
 * Created by jamieaitken on 03/05/2018 at 16:51
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;

use App\Utils\Formatters\MemoryUnitFormatter;
use App\Utils\Formatters\TimeUnitFormatter;

class NearlyProfilePDFLocation extends NearlyProfilePDFComponent
{

    public function create()
    {
        $html = "";
        foreach ($this->contents as $key => $value) {
            $html .= "<table>
                    <tr><td>Location:</td><td>" . $value['name'] . "</td></tr>
                    <tr><td>Downloaded:</td><td>" . MemoryUnitFormatter::format($value['totalDownload']) . "</td></tr>
                    <tr><td>Uploaded:</td><td>" . MemoryUnitFormatter::format($value['totalUpload']) . "</td></tr>
                    <tr><td>Up time:</td><td>" . TimeUnitFormatter::format($value['uptime']) . "</td></tr>
                    <tr><td>Logins:</td><td>" . $value['logins'] . "</td></tr></table>";
        }

        return $html;
    }
}