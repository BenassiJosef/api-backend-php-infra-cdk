<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 21/02/2017
 * Time: 08:04
 */

namespace App\Controllers\Integrations\Mikrotik;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\Uploads\_UploadsController;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\MikrotikConfig;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class _MikrotikConfigController
{

    private $path = 'config/';

    protected $em;

    protected $infrastructureCache;

    protected $uploads = _UploadsController::class;

    public $serial = '';

    public $command = '';

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->command             = '';
        $this->serial              = '';
        $this->uploads             = new _UploadsController();
    }

    public function buildConfig($command, $serial)
    {

        $file   = $this->uploads->storeString($command, 'conf', $this->path . $serial);
        $config = new MikrotikConfig($file, $serial);

        $this->em->persist($config);
        $this->em->flush();

        $this->updateWaiting($serial, true);
    }

    public function buildConfigSilent($command, $serial)
    {
        $config = new _Config($this->em);

        return $config->create($command, $serial);
    }

    public function getConfigs($serial)
    {
        $command = '';
        $configs = $this->em->getRepository(MikrotikConfig::class)->findBy([
            'serial'  => $serial,
            'deleted' => false
        ]);

        foreach ($configs as $config) {
            $command         .= $this->uploads->loadFile($config->file) . PHP_EOL;
            $config->deleted = true;
        }

        $this->em->flush();

        $this->updateWaiting($serial, false);

        return $command;
    }

    public function updateWaiting(string $serial, bool $status)
    {

        $oldInform = $this->infrastructureCache->fetch('informs:' . $serial);
        if ($oldInform !== false) {
            if ($status === true && $oldInform['waitingConfig'] === false) {
                $mp = new _Mixpanel();
                $mp->register('serial', $serial)->track('config_pending', ['serial' => $serial]);
            }
            $oldInform['waitingConfig'] = $status;
            $this->infrastructureCache->save('informs:' . $serial, $oldInform);
        }

        $getInform = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_object($getInform)) {
            $this->em->createQueryBuilder()
                ->update(MikrotikInform::class, 'm')
                ->set('m.waitingConfig', ':status')
                ->where('m.informId = :id')
                ->setParameter('id', $getInform->id)
                ->setParameter('status', $status)
                ->getQuery()
                ->execute();
        }
    }

    public function setSerial($serial)
    {
        $this->serial = $serial;

        return $this;
    }

    public function addCommand($command)
    {
        $this->command .= $command . PHP_EOL;

        return $this;
    }

    public function execute()
    {
        $this->buildConfigSilent($this->command, $this->serial);
        $this->command = '';
        $this->serial  = '';
    }
}
