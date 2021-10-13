<?php
/**
 * Created by jamieaitken on 04/04/2018 at 15:52
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\SMS;


use App\Utils\Strings;

class RandomOptOutCodeGenerator
{
    public static function getCode()
    {
        $cancelPrefixes = ['NO', 'STP', 'END', 'OPT'];

        return $cancelPrefixes[rand(0, 3)] . Strings::random(3);
    }
}