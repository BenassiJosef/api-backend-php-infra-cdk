<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/01/2017
 * Time: 17:05
 */

namespace App\Controllers\Locations\Devices;

use App\Controllers\Integrations\Mikrotik\_MikrotikDeviceController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\NodeDetails;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationsDevicesController extends _LocationSettingsController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function getLegacyUpdateDeviceRoute(Request $request, Response $response)
    {
        $status = $request->getAttribute('status');
        $id     = $request->getAttribute('id');

        $this->updateDeviceStatus($id, $status);

        $this->em->clear();

        return $response->withStatus(200);
    }

    public function postDeviceRoute(Request $request, Response $response)
    {

        $serial     = $request->getAttribute('serial');
        $validation = Validation::bodyCheck($request, ['mac', 'alias']);
        $body       = $request->getParsedBody();
        $loggedIn   = $request->getAttribute('user');

        if ($validation !== true) {
            $send = Http::status(400, $validation);
        } else {
            $send = $this->addDevice($body, $serial);
        }

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('device_create', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteDeviceRoute(Request $request, Response $response)
    {

        $serial   = $request->getAttribute('serial');
        $id       = $request->getAttribute('id');
        $send     = $this->deleteDevice($id, $serial);
        $loggedIn = $request->getAttribute('user');

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('device_delete', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateDeviceRoute(Request $request, Response $response)
    {

        $serial   = $request->getAttribute('serial');
        $id       = $request->getAttribute('id');
        $body     = $request->getParsedBody();
        $loggedIn = $request->getAttribute('user');

        $send = $this->updateDevice($body, $id, $serial);

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('device_update', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getDevicesRoute(Request $request, Response $response)
    {
        $send = $this->getDevices($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getDevices(string $serial)
    {
        $getDevices = $this->em->createQueryBuilder()
            ->select('u')
            ->from(NodeDetails::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.deleted = :false')
            ->setParameter('serial', $serial)
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();
        if (empty($getDevices)) {
            return Http::status(204);
        }

        return Http::status(200, $getDevices);
    }


    public function getByMac(string $mac = '', bool $loadCache = true)
    {
        $cache = $this->nearlyCache->fetch('nodeDetails:' . self::formatMac($mac));
        if ($cache !== false && $loadCache === true) {
            return Http::status(200, $cache);
        }

        $select = $this->em->createQueryBuilder()
            ->select('a')
            ->from(NodeDetails::class, 'a')
            ->where('a.mac = :mac')
            ->andWhere('a.deleted = 0')
            ->setParameter('mac', $mac)
            ->setMaxResults(1)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (empty($select)) {
            return Http::status(404, 'NODE_NOT_FOUND');
        }
        $this->nearlyCache->save('nodeDetails:' . self::formatMac($mac), $select[0]);

        return Http::status(200, $select[0]);
    }

    public function getByWanIP(string $ip)
    {
        $results = $this->em->createQueryBuilder()
            ->select('a')
            ->from(NodeDetails::class, 'a')
            ->where('a.wanIp = :ip')
            ->andWhere('a.deleted = 0')
            ->setParameter('ip', $ip)
            ->setMaxResults(1)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (empty($results)) {
            return Http::status(404, 'NODE_NOT_FOUND');
        }

        return Http::status(200, $results[0]);
    }

    public function checkForDeviceViaSerialAndMac(string $serial, string $mac)
    {
        return $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(NodeDetails::class, 'u')
            ->where('u.mac = :mac')
            ->andWhere('u.serial = :serial')
            ->setParameter('mac', $mac)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();
    }

    function addDevice(array $body = [], string $serial = '')
    {

        $deviceCheck = $this->checkForDeviceViaSerialAndMac($serial, $body['mac']);
        if (!empty($deviceCheck)) {
            return Http::status(400, 'DEVICE_WITH_MAC_EXISTS');
        }

        $device = new NodeDetails($serial, $body['alias'], $body['mac'], 0);
        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $device->port       = $body['port'];
                    $device->ip         = $body['ip'];
                    $device->type       = 'bypassed';
                    $mikrotikController = new _MikrotikDeviceController($this->em);

                    $this->em->persist($device);
                    $this->em->flush();

                    $mikrotikController->addDevice($device->id, $device->ip, $device->mac, $device->port, $serial);

                    break;
                case 'unifi':
                    $this->em->persist($device);
                    break;
                case 'openmesh':
                    $this->em->persist($device);
                    break;
            }
            if ($vendor === 'unifi' || $vendor === 'openmesh') {
                $this->em->flush();
            }
        }

        return Http::status(200, $device->getArrayCopy());
    }

    function updateDeviceStatus($id, $status)
    {

        $device = $this->em->getRepository(NodeDetails::class)->findOneBy([
            'id' => $id
        ]);

        if (is_null($device)) {
            return Http::status(404, 'NODE_NOT_FOUND');
        }

        //Maybe include some diffing down the line to check if device used to be offline,
        //include in alerts.
        //Not a biggie

        $device->status   = $status;
        $device->lastping = new \DateTime();
        $this->em->flush();

        return $device->getArrayCopy();
    }

    function updateDevice(array $body = [], string $id, string $serial = '')
    {
        $device = $this->em->getRepository(NodeDetails::class)->findOneBy([
            'id' => $id
        ]);

        if (is_null($device)) {
            return Http::status(404, 'NODE_NOT_FOUND');
        }

        if ($body['mac'] !== $device->mac) {
            $deviceCheck = $this->checkForDeviceViaSerialAndMac($serial, $body['mac']);
            if (!empty($deviceCheck)) {
                return Http::status(400, 'DEVICE_WITH_MAC_EXISTS');
            }
        }

        $canChange = ['mac', 'ip', 'port', 'alias', 'wanIp', 'status'];
        foreach ($canChange as $item) {
            if (isset($body[$item])) {
                $device->$item = $body[$item];
            }
        }

        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $mikrotikController = new _MikrotikDeviceController($this->em);
                    $mikrotikController->deleteDevice($id, $serial);
                    $mikrotikController->addDevice($id, $device->ip, $device->mac, $device->port, $serial);
                    break;
                case 'UNIFI':
                    break;
                case 'OPENMESH':
                    break;
            }
        }

        $this->em->persist($device);
        $this->em->flush();


        $this->nearlyCache->delete('nodeDetails:' . self::formatMac($device->mac));

        return Http::status(200, $device->getArrayCopy());
    }

    public function getExistingBySerial(string $serial)
    {
        $devices = $this->em->getRepository(NodeDetails::class)->findBy([
            'serial'  => $serial,
            'deleted' => false
        ]);

        $arrayOfCopies = [];

        foreach ($devices as $device) {
            $arrayOfCopies[] = $device->getArrayCopy();
        }


        return Http::status(200, $arrayOfCopies);
    }

    public function deleteDevicesBySerial(string $serial)
    {
        $this->em->createQueryBuilder()
            ->delete(NodeDetails::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->execute();
    }

    function deleteDevice(string $id, string $serial)
    {
        $device = $this->em->getRepository(NodeDetails::class)->findOneBy([
            'id'      => $id,
            'deleted' => false
        ]);

        if (is_null($device)) {
            return Http::status(404, 'DEVICE_NOT_FOUND');
        }

        $vendor = $this->getVendor($serial);

        if ($vendor !== false) {
            switch ($vendor) {
                case 'mikrotik':
                    $mikrotikController = new _MikrotikDeviceController($this->em);
                    $mikrotikController->deleteDevice($id, $serial);
                    break;
                case 'unifi':
                    break;
                case 'openmesh':
                    break;
            }
        }

        $device->deleted = true;

        $this->nearlyCache->delete('nodeDetails:' . self::formatMac($device->mac));
        $this->em->persist($device);
        $this->em->flush();

        return Http::status(200, $device->getArrayCopy());
    }


    public function deleteDeviceByMac(string $mac)
    {
        $this->em->createQueryBuilder()
            ->delete(NodeDetails::class, 'u')
            ->where('u.mac = :m')
            ->setParameter('m', $mac)
            ->getQuery()
            ->execute();
    }

    static function formatMac(string $mac)
    {
        return str_replace(':', '-', $mac);
    }
}
