<?php
/**
 * Created by patrickclover on 30/12/2017 at 12:40
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\UniFi;

use Doctrine\ORM\EntityManager;

class _Client
{
    protected $unifiSettings;

    public function __construct(EntityManager $em)
    {
        $this->unifiSettings = new _UniFiSettingsController($em);
    }

    public function deauth($mac, $serial)
    {
        $settings = $this->unifiSettings->settings($serial);
        if ($settings['status'] !== 200) {
            $setting = $settings['message'];
        }

        $unifi = new _UniFi($setting['username'], $setting['password'], $setting['hostname'], $setting['unifiId']);

        return $unifi->unauthClient($mac);
    }
}
