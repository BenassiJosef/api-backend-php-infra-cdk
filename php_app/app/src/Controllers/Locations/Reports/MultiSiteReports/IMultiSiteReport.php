<?php
/**
 * Created by jamieaitken on 21/05/2018 at 12:34
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


interface IMultiSiteReport
{
    public function getData(array $serial, array $options);
}