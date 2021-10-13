<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:43
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _MikrotikWiFiController extends _MikrotikConfigController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function setWiFi($serial, $disabled, $ssid)
    {
        $disabledCmd = 'yes';
        if ($disabled === false) {
            $disabledCmd = 'no';
        }

        $command = '/interface wireless set ssid="' . $ssid . '" disabled=' . $disabledCmd . ' numbers=wifi';
        $this->buildConfig($command, $serial);
    }
}
