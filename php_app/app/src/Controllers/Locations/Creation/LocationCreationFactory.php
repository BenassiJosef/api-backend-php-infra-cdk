<?php

/**
 * Created by jamieaitken on 28/01/2019 at 10:24
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Creation;


use App\Controllers\Integrations\IgniteNet\_IgniteNetCreationController;
use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Integrations\Radius\_RadiusCreationController;
use App\Controllers\Integrations\UniFi\_UniFiCreationController;
use Doctrine\ORM\EntityManager;

class LocationCreationFactory
{
    private $em;
    private $serial = null;
    private $vendor;

    private $ip;
    private $cpuStatus;
    private $model;
    private $osVersion;

    public function __construct(EntityManager $entityManager, string $vendor, ?string $serial)
    {
        $this->em     = $entityManager;
        $this->vendor = strtolower($vendor);
        $this->serial = $serial;
    }

    public function getInstance()
    {


        $radius = new _RadiusCreationController($this->em);
        $create = $radius;
        if ($this->vendor === 'ignitenet') {
            $create = new _IgniteNetCreationController($this->em);
        } elseif ($this->vendor === 'unifi') {
            $create = new _UniFiCreationController($this->em);
        } elseif ($radius->isRadiusVendor($this->vendor)) {
            $create = $radius;
            $create->setIsRadius(true);
        } elseif ($this->vendor === 'mikrotik') {
            $create = new MikrotikCreationController(
                $this->em,
                $this->ip,
                $this->cpuStatus,
                $this->model,
                $this->osVersion
            );
        }

        $create->setSerial($this->serial);
        $create->setVendor($this->vendor);

        return $create;
    }

    public function setMikrotikInformData(?string $ip, ?int $cpu, ?string $model, ?string $osVersion)
    {
        $this->ip        = $ip;
        $this->cpuStatus = $cpu;
        $this->model     = $model;
        $this->osVersion = $osVersion;
    }
}
