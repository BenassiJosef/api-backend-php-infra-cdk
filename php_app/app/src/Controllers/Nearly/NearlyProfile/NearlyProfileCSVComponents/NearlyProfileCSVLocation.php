<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:31
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


use App\Utils\Formatters\MemoryUnitFormatter;
use App\Utils\Formatters\TimeUnitFormatter;

class NearlyProfileCSVLocation extends NearlyProfileCSVComponent
{


    public function create()
    {
        $newArray = [];
        foreach ($this->contents as $key => $value) {
            $newArray[] = [
                $value['name'],
                MemoryUnitFormatter::format($value['totalDownload']),
                MemoryUnitFormatter::format($value['totalUpload']),
                TimeUnitFormatter::format($value['uptime']),
                $value['logins']
            ];
        }

        $this->contents = $newArray;
    }
}