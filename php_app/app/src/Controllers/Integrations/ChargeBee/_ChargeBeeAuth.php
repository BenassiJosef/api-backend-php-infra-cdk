<?php

namespace App\Controllers\Integrations\ChargeBee;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 29/06/2017
 * Time: 16:18
 */

class _ChargeBeeAuth
{
    static public function initilize()
    {
        \ChargeBee_Environment::configure(getenv('chargebee_site_name'), getenv('chargebee_site_key'));
    }
}
