<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:30
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


use App\Utils\Formatters\MemoryUnitFormatter;

class NearlyProfileCSVDevices extends NearlyProfileCSVComponent
{

    public function create()
    {
        $newArray = [];
        foreach ($this->contents['devices'] as $key => $device) {
            $newArray[] = [
                $device['mac'],
                MemoryUnitFormatter::format($device['dataDown']),
                MemoryUnitFormatter::format($device['dataUp']),
                $device['name'],
                $device['version'],
                $device['brand'],
                $device['model']
            ];
        }

        $this->contents = $newArray;
    }
}