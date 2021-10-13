<?php


namespace App\Controllers\Locations\Reports\Overview;

/**
 * Interface View
 * @package App\Controllers\Locations\Reports\Overview
 */
interface View
{
    /**
     * @param Overview $overview
     * @param array $serials
     * @return Overview
     */
    public function addDataToOverview(Overview $overview, array $serials): Overview;
}