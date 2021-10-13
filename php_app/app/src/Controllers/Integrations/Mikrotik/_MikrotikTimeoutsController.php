<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 17:27
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _MikrotikTimeoutsController extends _MikrotikConfigController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function setTimeouts($serial, $type, $free = [], $paid = [])
    {

        $command = '';
        if ($type === 0 || $type === 2) {
            $command    .= '/ip hotspot user profile set numbers=free keepalive-timeout=' . $free['idle'] . ' idle-timeout=' . $free['idle'] . ' session-timeout=' . $free['session'] . PHP_EOL;
            $subCommand = $this->additionalTimeouts($free['idle']);
        }

        if ($type === 1 || $type === 2) {
            $command    .= '/ip hotspot user profile set numbers=paid keepalive-timeout=' . $paid['idle'] . ' idle-timeout=' . $paid['idle'] . ' session-timeout=' . $paid['session'] . PHP_EOL;
            $subCommand = $this->additionalTimeouts($paid['idle']);
        }

        $command .= $subCommand;

        $this->buildConfig($command, $serial);
    }

    public function additionalTimeouts($time)
    {

        $command = '/ip dhcp-server set lease-time=' . $time . ' [find]' . PHP_EOL;
        $command .= '/ip hotspot set idle-timeout=' . $time . ' [find]' . PHP_EOL;

        return $command;
    }
}
