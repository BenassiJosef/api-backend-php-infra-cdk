<?php
/**
 * Created by jamieaitken on 16/05/2018 at 13:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;


class NearlyProfileCSVMarketingEvents extends NearlyProfileCSVComponent
{

    public function create()
    {
        $newArray = [];

        foreach ($this->contents['marketing']['locations'] as $key => $location) {
            if (isset($location['events'])) {
                foreach ($location['events'] as $event) {
                    $newArray[] = [
                        $location['name'],
                        $event['type'],
                        $event['timestamp']->format('g:ia \o\n l jS F Y')
                    ];
                }
            }
        }

        $this->contents = $newArray;
    }
}