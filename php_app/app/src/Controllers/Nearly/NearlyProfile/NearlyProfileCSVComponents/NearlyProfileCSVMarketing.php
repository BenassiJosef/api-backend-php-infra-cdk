<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:35
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


class NearlyProfileCSVMarketing extends NearlyProfileCSVComponent
{
    public function create()
    {
        $newArray = [];
        foreach ($this->contents['marketing']['locations'] as $key => $location) {
            $newArray[] = [
                $location['name'],
                $location['email'],
                $location['sms']
            ];
        }

        $this->contents = $newArray;
    }
}