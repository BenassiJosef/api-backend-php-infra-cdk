<?php
/**
 * Created by patrickclover on 30/12/2017 at 12:48
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _Client
{
    protected $config;

    public function __construct(EntityManager $em)
    {
        $this->config = new _Config($em);
    }

    public function deauth($mac, $serial)
    {
        $command = '/ip hotspot active remove [find mac-address=' . $mac . ']';

        return $this->config->create($command, $serial);
    }
}
