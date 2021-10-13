<?php
/**
 * Created by jamieaitken on 08/03/2018 at 17:54
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Utils;


class MacFormatter
{

    public static function format($mac)
    {
        return str_replace('-', ':', strtoupper($mac));
    }
}