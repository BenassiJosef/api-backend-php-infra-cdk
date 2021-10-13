<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:46
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _MikrotikBandwidthController extends _MikrotikConfigController
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function setBandwidth($type, $upload = 0, $download = 0)
    {
        $download = $download . 'k';
        $upload   = $upload . 'k';
        $command  = '/ip hotspot user profile set numbers=' . $type . ' rate-limit=' . $upload . '/' . $download . PHP_EOL;

        return $command;
    }

    public function setBandwidths($serial, $type, $free = [], $paid = [])
    {

        $command = '';
        if ($type === 0 || $type === 2) {
            $command .= $this->setBandwidth('free', $free['upload'], $free['download']);
        }

        if ($type === 1 || $type === 2) {
            $command .= $this->setBandwidth('paid', $paid['upload'], $paid['download']);
        }

        $this->buildConfig($command, $serial);
    }
}
