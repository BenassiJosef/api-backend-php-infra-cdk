<?php
/**
 * Created by jamieaitken on 28/01/2019 at 10:16
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations;


interface ILocationCreation
{
    public function createInform(?string $serial);

    public function createBespokeLogic(string $serial, string $vendor);

    public function deleteBespokeLogic(string $serial, string $vendor);

    public function isLocationBeingReactivated(string $serial);
}