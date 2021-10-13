<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:17
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Utils\Formatters;

class MemoryUnitFormatter
{
    public static function format(int $value = null)
    {
        if (is_null($value)) {
            $value = 0;
        }
        $base     = log($value, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        return round(pow(1024, $base - floor($base)), 2) . ' ' . $suffixes[floor($base)];
    }
}