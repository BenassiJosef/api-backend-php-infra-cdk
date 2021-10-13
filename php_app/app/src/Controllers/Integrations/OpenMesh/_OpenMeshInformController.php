<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 13/03/2017
 * Time: 18:04
 */

namespace App\Controllers\Integrations\OpenMesh;

use App\Controllers\Clients\_ClientsActiveController;
use App\Controllers\Clients\_ClientsUpdateController;
use App\Controllers\Locations\_LocationsInformController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Controllers\Locations\iInform;
use App\Models\Locations\Informs\Inform;
use App\Models\NodeDetails;
use App\Package\Vendors\Information;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _OpenMeshInformController implements iInform
{
    protected $em;
    protected $infrastructureCache;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function openMeshInformRoute(Request $request, Response $response)
    {
        $mac         = $request->getAttribute('ap');
        $queryParams = $request->getQueryParams();
        $ip          = $request->getHeader('X-Forwarded-For');
        $body        = $request->getBody();
        $arr         = explode(PHP_EOL, $body);
        $split       = array_chunk($arr, 3);
        $nodes       = new _LocationsDevicesController($this->em);
        $now         = new \DateTime();

        if (isset($queryParams['ip'])) {
            $ip = $queryParams['ip'];
        }

        if (is_array($ip) && !empty($ip)) {
            $ip = $ip[0];
        }
        if (is_string($ip) && stripos($ip, ',') !== false) {
            $mutipleIps = explode(',', $ip);
            if (count($mutipleIps) >= 2) {
                $ip = $mutipleIps[0];
            }
        } elseif (is_null($ip) || empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $accessPoint = $nodes->getByMac($mac);

        if ($accessPoint['status'] === 404) {
            $getByWanIp = $nodes->getByWanIP($ip);

            if ($getByWanIp['status'] === 200) {
                $nodeDetails = new NodeDetails($getByWanIp['message']['serial'], 'Manually Added AP', $mac, $ip);
                $this->em->persist($nodeDetails);
                $this->em->flush();

                return Http::status(200, 'ACCESS_POINT_ADDED');
            } else {
                return Http::status(400, 'COULD_NOT_GET_NODE_BY_MAC_OR_WAN_IP');
            }
        } elseif ($accessPoint['status'] === 200) {
            if (!is_null($accessPoint['message']['serial'])) {
                $serial = $accessPoint['message']['serial'];

                $clients       = new _ClientsActiveController($this->em);
                $activeClients = $clients->activeClients($serial);
                $clientUpdate  = new _ClientsUpdateController($this->em);

                if ($activeClients['status'] === 200) {
                    $clientsArray = [];

                    foreach ($split as $key => $value) {
                        if (count($value) === 3) {
                            $mac = $this->getStringBetween($value[0], 'Station ', ' (');
                            preg_match_all('!\d+!', $value[1], $dataUp);
                            preg_match_all('!\d+!', $value[2], $dataDown);

                            $clientsArray[] = [
                                'mac'  => strtoupper($mac),
                                'down' => (int) $dataDown[0][0],
                                'up'   => (int) $dataUp[0][0]
                            ];
                        }
                    }

                    foreach ($clientsArray as $key => $value) {
                        foreach ($activeClients['message'] as $client) {
                            if ($value['mac'] === $client['mac']) {
                                $mac      = $value['mac'];
                                $download = $value['down'];
                                $upload   = $value['up'];

                                $clientUpdate->update($download, $upload, $mac, $serial, $client['ip']);
                            }
                        }
                    }
                }

                $inform       = new _LocationsInformController($this->em);
                $createInform = $inform->createInform($serial, $ip, 'OPENMESH', [
                    'mac' => $mac
                ]);

                $this->em->flush();

                if (is_bool($createInform)) {
                    return $response->withJson($createInform, 400);
                }

                if (!empty($createInform) || $accessPoint['message']['status'] === false) {
                    $nodes->updateDevice(
                        ['status' => true, 'wanIp' => $ip, 'lastping' => $now],
                        $accessPoint['message']['id'],
                        $serial
                    );
                }

                return $response->withJson($createInform, 200);
            }
        }

        return $response->withJson('FAILED_TO_INFORM', 400);
    }

    function getStringBetween($str, $from, $to)
    {
        $sub = substr($str, strpos($str, $from) + strlen($from), strlen($str));

        return substr($sub, 0, strpos($sub, $to));
    }

    public function createInform(string $serial, string $ip, string $vendor, array $extraInformData)
    {
        $getLastInform  = $this->getInform($serial);
        $informRequired = false;
        if (is_null($getLastInform) && $vendor == 'OPENMESH') {
            $vendorInformation = new Information($this->em);
            $inform = new Inform($serial, $ip, true, $vendor, $vendorInformation->getFromKey('openmesh'));
            $this->em->persist($inform);
            $this->em->flush();
        }

        if (is_array($getLastInform) || is_object($getLastInform)) {
            $now            = new \DateTime();
            $lastInformTime = new \DateTime($getLastInform['timestamp']->date);
            if ($getLastInform['status'] === false) {
                $getLastInform['onlineAt'] = $now;
                $informRequired            = true;
            }

            if ($getLastInform['ip'] !== $ip) {
                $node = $this->em->getRepository(NodeDetails::class)->findOneBy([
                    'serial' => $serial,
                    'ip'     => $getLastInform['ip'],
                    'mac'    => $extraInformData['mac']
                ]);

                if (is_object($node)) {
                    $node->wanIp = $ip;
                    $this->em->persist($node);
                    $this->em->flush();
                }

                $informRequired = true;
            }

            if ($getLastInform['ip'] !== $ip && $getLastInform['status'] === true) {
                $informRequired = true;
            }

            if ($getLastInform['ip'] !== $ip && $getLastInform['status'] === false) {
                $informRequired = true;
            }

            $shouldInform = ($now > $lastInformTime->modify('+5 minutes'));
            $dataToSend   = [
                'ip'            => $ip,
                'status'        => true,
                'vendor'        => $vendor,
                'waitingConfig' => $getLastInform['waitingConfig'],
                'timestamp'     => $now,
                'onlineAt'      => $getLastInform['onlineAt']
            ];

            $this->saveToCache($serial, $dataToSend);
            if ($shouldInform || $informRequired) {
                $this->setInform($serial, $dataToSend);
            }

            return '';
        }

        return false;
    }


    public function getInform(string $serial)
    {
        $inform = $this->getFromCache($serial);
        if ($inform !== false) {
            return $inform;
        }

        return $this->getFromPersistentStorage($serial);
    }

    public function setInform(string $serial, array $dataset)
    {
        $inform = $this->saveToPersistentStorage($serial, $dataset);

        return (array) $inform;
    }

    public function getFromCache(string $serial)
    {
        return $this->infrastructureCache->fetch('informs:' . $serial);
    }

    public function saveToCache(string $serial, array $dataset)
    {
        return $this->infrastructureCache->save('informs:' . $serial, $dataset);
    }

    public function getFromPersistentStorage(string $serial)
    {
        $inform = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);
        if (is_object($inform)) {
            $informArray = $inform->getArrayCopy();
            $this->saveToCache($serial, $informArray);

            return $informArray;
        }

        return null;
    }

    public function saveToPersistentStorage(string $serial, array $dataset)
    {
        $locationExists = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_object($locationExists)) {
            $locationExists->timestamp = $dataset['timestamp'];
            $locationExists->ip        = $dataset['ip'];
            $locationExists->status    = $dataset['status'];
            $locationExists->vendor    = $dataset['vendor'];
            $locationExists->offlineAt = $dataset['onlineAt'];

            $this->em->persist($locationExists);
            $this->em->flush();

            $this->saveToCache($serial, $locationExists->getArrayCopy());

            return $locationExists->getArrayCopy();
        }

        return null;
    }
}
