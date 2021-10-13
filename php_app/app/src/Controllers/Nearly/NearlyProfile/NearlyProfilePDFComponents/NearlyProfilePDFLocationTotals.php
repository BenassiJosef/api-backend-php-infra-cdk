<?php
/**
 * Created by jamieaitken on 03/05/2018 at 20:54
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;


use App\Utils\Formatters\MemoryUnitFormatter;
use App\Utils\Formatters\TimeUnitFormatter;

class NearlyProfilePDFLocationTotals extends NearlyProfilePDFComponent
{

    public function create()
    {
        return "
            <table>
                <tr><td>Downloaded:</td><td>" . MemoryUnitFormatter::format($this->contents['locations']['totals']['download']) . "</td></tr>
                <tr><td>Uploaded:</td><td>" . MemoryUnitFormatter::format($this->contents['locations']['totals']['upload']) . "</td></tr>
                <tr><td>Up time:</td><td>" . TimeUnitFormatter::format($this->contents['locations']['totals']['uptime']) . "</td></tr>
                <tr><td>Logins:</td><td>" . $this->contents['locations']['totals']['logins'] . "</td></tr>
                <tr><td>Total Sum of Payments:</td><td>" . $this->contents['locations']['totals']['payments']['sum'] . "</td></tr>
                <tr><td>Total Payments:</td><td>" . $this->contents['locations']['totals']['payments']['count'] . "</td></tr>
            </table>";
    }
}