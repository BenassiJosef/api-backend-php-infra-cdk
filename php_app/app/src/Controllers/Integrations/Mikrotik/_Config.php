<?php
/**
 * Created by patrickclover on 30/12/2017 at 12:48
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Mikrotik;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\Uploads\_UploadsController;
use App\Models\MikrotikConfig;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class _Config
{

    private $path = 'config/';
    protected $uploads = _UploadsController::class;
    protected $em;
    protected $infrastructureCache;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->uploads             = new _UploadsController();
    }

    public function create($command, $serial)
    {
        $fileUrl = $this->uploads->storeString($command, 'conf', $this->path . $serial);
        $config  = new MikrotikConfig($fileUrl, $serial);

        $this->em->persist($config);
        $this->em->flush();

        $oldInform = $this->infrastructureCache->fetch('informs:' . $serial);

        if ($oldInform !== false) {
            if ($oldInform['waitingConfig'] === false) {
                $mp = new _Mixpanel();
                $mp->register('serial', $serial)->track('config_pending', ['serial' => $serial]);
            }
            $oldInform['waitingConfig'] = true;
            $this->infrastructureCache->save('informs:' . $serial, $oldInform);
        }

        return $config->getArrayCopy();
    }
}
