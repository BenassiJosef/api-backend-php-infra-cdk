<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:20
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Utils\Formatters;


class TimeUnitFormatter
{

    public static function format(int $value = null)
    {
        if (is_null($value)) {
            $value = 0;
        }
        $base     = log($value, 60);
        $suffixes = ['Seconds', 'Minutes', 'Hours'];

        return round(pow(60, $base - floor($base)), 2) . ' ' . $suffixes[floor($base)];
    }
}