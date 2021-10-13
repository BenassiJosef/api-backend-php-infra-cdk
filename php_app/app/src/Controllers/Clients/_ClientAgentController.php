<?php

namespace App\Controllers\Clients;

use App\Models\Device\DeviceBrowser;
use App\Models\Device\DeviceOs;
use App\Models\User\UserAgent;
use App\Models\User\UserDevice;
use Doctrine\ORM\EntityManager;

class _ClientAgentController
{

    protected $em;

    /**
     * _ClientsController constructor.
     * @param $em
     */

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function create($agent)
    {

        $device  = $this->createDevice($agent['device']);
        $os      = null;
        $browser = null;
        if (isset($agent['os'])) {
            if (is_array($agent['os'])) {
                $os = $this->createOs($agent['os']);
            }
        }
        if (isset($agent['browser'])) {
            if (is_array($agent['browser'])) {
                $browser = $this->createBrowser($agent['browser']);
            }
        }
        if (!is_null($os) && !is_null($browser)) {
            $this->createUserAgent($device, $os, $browser);
        }
        $this->em->flush();

        return [];
    }

    public function createUserAgent(UserDevice $device, DeviceOs $os, DeviceBrowser $browser)
    {
        $match = false;
        foreach ($device->getAgents() as $agent) {
            $model = $agent;
            if ($agent->deviceOsId === $os->id && $agent->deviceBrowserId === $browser->id) {
                $match = true;
                continue;
            }
        }
        if ($match === false) {
            $model = new UserAgent();
            $model->setDevice($device);
            $model->deviceOsId      = $os->id;
            $model->deviceBrowserId = $browser->id;
            $this->em->persist($model);
        }

        return $model;
    }

    /**
     * @param array $device
     * @return UserDevice|null|object
     */

    public function createDevice(array $device)
    {
        $model = $this->em->getRepository(UserDevice::class)->findOneBy([
            'mac' => $device['mac']
        ]);

        if (is_null($model)) {
            $model = new UserDevice($device);
            $this->em->persist($model);
        }

        return $model;
    }

    /**
     * @param array $os
     * @param UserAgent $agent
     * @return DeviceOs|null|object
     */

    public function createOs(array $os)
    {
        $model = $this->em->getRepository(DeviceOs::class)->findOneBy([
            'name'      => $os['name'],
            'shortName' => $os['short_name'],
            'version'   => $os['version'],
            'platform'  => $os['platform'],
        ]);

        if (is_null($model)) {
            $model = new DeviceOs($os);
            $this->em->persist($model);
        }

        return $model;
    }

    /**
     * @param array $browser
     * @param UserAgent $agent
     * @return DeviceBrowser|null|object
     */

    public function createBrowser(array $browser)
    {
        $model = $this->em->getRepository(DeviceBrowser::class)->findOneBy([
            'type'          => $browser['type'],
            'name'          => $browser['name'],
            'shortName'     => $browser['short_name'],
            'version'       => $browser['version'],
            'engine'        => $browser['engine'],
            'engineVersion' => $browser['engine_version']
        ]);

        if (is_null($model)) {
            $model = new DeviceBrowser($browser);
            $this->em->persist($model);
        }

        return $model;
    }
}
