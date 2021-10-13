<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 13/03/2017
 * Time: 18:07
 */

namespace App\Controllers\Integrations\Mikrotik;


use App\Controllers\Clients\_ClientsController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Locations\Creation\LocationCreationFactory;
use App\Controllers\Locations\iInform;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Models\Notifications\Notification;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _MikrotikInformController implements iInform
{
    protected $em;
    protected $infrastructureCache;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \phpmailerException
     */

    public function mikrotikInformLegacy(Request $request, Response $response, $args)
    {
        $serial      = $request->getAttribute('serial');
        $cpu         = $request->getAttribute('cpu');
        $model       = $request->getAttribute('model');
        $queryParams = $request->getQueryParams();
        $message     = '';

        $ip = $request->getHeader('X-Forwarded-For');

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
        } else {
            if (is_null($ip) || empty($ip)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        $message .= 'Serial at risk ' . $serial . PHP_EOL;
        $message .= 'IP: ' . $ip . PHP_EOL;
        $message .= 'Model: ' . $model;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */

    public function mikrotikInform(Request $request, Response $response, $args)
    {
        $serial                 = $request->getAttribute('serial');
        $cpu                    = $request->getAttribute('cpu');
        $operatingSystemVersion = $request->getAttribute('os');
        $model                  = $request->getQueryParam('model', null);
        $queryParams            = $request->getQueryParams();
        $response               = $response->withHeader('Content-type', 'text/plain');
        $ip                     = $request->getHeader('X-Forwarded-For');

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
        } else {
            if (is_null($ip) || empty($ip)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        $create = $this->createInform(
            $serial,
            $ip,
            'MIKROTIK',
            ['cpu' => $cpu, 'model' => $model, 'osVersion' => $operatingSystemVersion]
        );

        if ($create === false) {
            return $response
                ->withStatus(400)
                ->write($create);
        }

        return $response
            ->withStatus(200)
            ->write($create);
    }

    public function createInform(string $physicalSerial, string $ip, string $vendor, array $extraData)
    {
        if (strlen($physicalSerial) !== 12) {
            return false;
        }

        $virtualSerial = $this->getVirtualSerialFromPhysicalSerial($physicalSerial, $ip, $extraData);
        $getLastInform  = $this->getInform($virtualSerial);
        $informRequired = false;

        if (is_null($getLastInform) && $vendor === 'MIKROTIK') {
            $conf    = new _MikrotikConfigController($this->em);
            $command = '/system script remove [find name=engine_checkin]' . PHP_EOL;
            $command .= '/system scheduler remove [find name=engine_checkin]';
            $conf->buildConfig($command, $virtualSerial);
        }

        if (is_array($getLastInform) || is_object($getLastInform)) {
            $cpuWarning     = false;
            $now            = new \DateTime();
            $lastInformTime = new \DateTime($getLastInform['timestamp'] ? $getLastInform['timestamp']->date : $now);

            if ($getLastInform['ip'] !== $ip) {
                /** IP CHANGE **/
                $informRequired = true;
            }

            if ($getLastInform['ip'] !== $ip && $getLastInform['status'] === true) {
                /** Possible dynamic ip **/
                $informRequired = true;
            }

            if ($getLastInform['ip'] !== $ip && $getLastInform['status'] === false) {
                /** Possible location move **/
                $informRequired = true;
            }
            $cpuCheck = $this->cpuStatus($getLastInform['cpu'], $extraData['cpu']);
            if ($cpuCheck) {
                $cpuWarning     = true;
                $informRequired = true;
            }

            if ($getLastInform['cpuWarning'] === true && $extraData['cpu'] < 85) {
                $cpuWarning     = false;
                $informRequired = true;
            }

            if ($getLastInform['status'] === false) {
                $getLastInform['onlineAt'] = $now;
                $mp                        = new _Mixpanel();
                $mp->register('serial', $virtualSerial)->track('location_online', $getLastInform);
                $deviceFlicker = ($now > $lastInformTime->modify('+12 minutes'));

                if ($deviceFlicker) {
                    if ($getLastInform['master'] === true) {
                        $slaves = $this->getSlaves($virtualSerial);
                        if (!empty($slaves)) {
                            $this->setSlaves($slaves, [
                                'ip'            => $ip,
                                'status'        => true,
                                'vendor'        => $vendor,
                                'model'         => $extraData['model'],
                                'osVersion'     => $extraData['osVersion'],
                                'cpu'           => $extraData['cpu'],
                                'cpuWarning'    => $cpuWarning,
                                'masterSite'    => $virtualSerial,
                                'waitingConfig' => $getLastInform['waitingConfig'],
                                'timestamp'     => $now,
                                'onlineAt'      => $getLastInform['onlineAt']
                            ]);
                        }
                    }
                }

                if ($getLastInform['status'] === false) {

                    $publisher = new QueueSender();
                    $publisher->sendMessage([
                        'site'   => $virtualSerial,
                        'status' => 'online'
                    ], QueueUrls::INFORM);

                    $newNotification         = new Notification(
                        $virtualSerial,
                        'Location Online',
                        'network_online',
                        '/' . $virtualSerial . '/overview'
                    );
                    $newNotification->serial = $virtualSerial;
                    $this->em->persist($newNotification);
                    $this->em->flush();

                    $publisher->sendMessage([
                        'notificationContent' => [
                            'objectId' => $virtualSerial,
                            'title'    => 'Location Online',
                            'kind'     => 'network_online',
                            'link'     => '/overview',
                            'serial'   => $virtualSerial
                        ]
                    ], QueueUrls::NOTIFICATION);
                }

                $informRequired = true;
            }

            $masterSiteCheck = $getLastInform['master'];

            $shouldInform = ($now > $lastInformTime->modify('+5 minutes'));
            $dataToSend   = [
                'ip'            => $ip,
                'status'        => true,
                'vendor'        => $vendor,
                'model'         => $extraData['model'],
                'osVersion'     => $extraData['osVersion'],
                'cpu'           => $extraData['cpu'],
                'cpuWarning'    => $cpuWarning,
                'master'        => $masterSiteCheck,
                'masterSite'    => $getLastInform['masterSite'],
                'waitingConfig' => $getLastInform['waitingConfig'],
                'timestamp'     => $now,
                'onlineAt'      => $getLastInform['onlineAt']
            ];
            $createCached = $this->createWholeInform($dataToSend, [
                'createdAt' => $getLastInform['createdAt']
            ]);

            $this->saveToCache($virtualSerial, $createCached);

            if ($shouldInform || $informRequired === true) {

                $this->setInform($virtualSerial, $dataToSend);
            }



            $mikrotikConfig = new _MikrotikConfigController($this->em);

            return $mikrotikConfig->getConfigs($virtualSerial);
        }

        return false;
    }

    public function cpuStatus($cpuPrevious, $cpuNow)
    {
        if ($cpuPrevious >= 85 && $cpuNow >= 85) {
            return true;
        }

        return false;
    }

    public function setSlaves(array $slaves, $arr)
    {
        foreach ($slaves as $slave) {
            $this->setInform($slave['serial'], $arr);
        }
    }

    public function getSlaves($serial)
    {
        $results = $this->em->createQueryBuilder()
            ->select('i')
            ->from(MikrotikInform::class, 'l')
            ->join(Inform::class, 'i', 'WITH', 'l.informId = i.id')
            ->where('l.masterSite = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        $send = [];

        if (!empty($results)) {
            $send = $results;
        }

        return $send;
    }

    public function clientData(Request $request, Response $response, $args)
    {

        $data = $request->getAttribute('data');

        /**
         * CONVERT STRING TO JSON
         */

        $data      = substr($data, 0, -1);
        $data      = str_replace("'", '"', $data);
        $json      = '{' . $data . '}';
        $obj_array = json_decode($json);

        $clients = new _ClientsController($this->em);

        foreach ($obj_array as $key => $value) {
            $array = explode(',', $value);

            $mac      = $array[0];
            $ip       = $array[1];
            $download = $array[2];
            $upload   = $array[3];
            $serial   = $array[5];

            $clients->update($download, $upload, $mac, $serial, $ip);
        }
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
        $inform = $this->em->createQueryBuilder()->select('i, m')
            ->from(Inform::class, 'i')
            ->join(MikrotikInform::class, 'm', 'WITH', 'i.id = m.informId')
            ->where('i.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($inform)) {
            $wholeInform = $this->createWholeInform($inform[0], $inform[1]);
            $this->saveToCache($serial, $wholeInform);

            return $wholeInform;
        }

        return null;
    }

    public function saveToPersistentStorage(string $serial, array $dataset)
    {
        $baseInform         = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);
        $findMikrotikInform = $this->em->getRepository(MikrotikInform::class)->findOneBy([
            'informId' => $baseInform->id
        ]);

        if (is_object($findMikrotikInform)) {
            $baseInform->timestamp = $dataset['timestamp'];
            $baseInform->onlineAt  = $dataset['onlineAt'];
            $baseInform->ip        = $dataset['ip'];
            $baseInform->status    = $dataset['status'];
            $baseInform->vendor    = $dataset['vendor'];
            $this->em->persist($baseInform);

            $findMikrotikInform->cpu           = $dataset['cpu'];
            $findMikrotikInform->cpuWarning    = $dataset['cpuWarning'];
            $findMikrotikInform->masterSite    = $dataset['masterSite'];
            $findMikrotikInform->master        = $dataset['master'];
            $findMikrotikInform->masterSite    = $dataset['masterSite'];
            $findMikrotikInform->model         = $dataset['model'];
            $findMikrotikInform->waitingConfig = $dataset['waitingConfig'];
            $findMikrotikInform->osVersion     = $dataset['osVersion'];
            $this->em->persist($findMikrotikInform);
            $this->em->flush();


            $inform = $this->createWholeInform($baseInform->getArrayCopy(), $findMikrotikInform->getArrayCopy());

            return $inform;
        }

        return null;
    }

    public function createWholeInform(array $baseInform, array $extraInform)
    {
        return array_merge($baseInform, $extraInform);
    }

    public function getVirtualSerialFromPhysicalSerial(string $physicalSerial, $ip, array $extraData)
    {
        $getVirtualSerial = $this->em->createQueryBuilder()
            ->select('u.virtualSerial')
            ->from(MikrotikSymlinkSerial::class, 'u')
            ->where('u.physicalSerial = :serial')
            ->setParameter('serial', $physicalSerial)
            ->getQuery()
            ->getArrayResult();

        if (empty($getVirtualSerial)) {
            $locationCreationFactory = new LocationCreationFactory($this->em, 'mikrotik', $physicalSerial);
            $locationCreationFactory->setMikrotikInformData(
                $ip,
                $extraData['cpu'],
                $extraData['model'],
                $extraData['osVersion']
            );

            $instance = $locationCreationFactory->getInstance();

            $serial = $instance->createInform($instance->getSerial());

            $instance->initialiseLocationSettings($instance->getSerial());

            $instance->locationAccessController->initialiseLocationAccess($instance->getSerial());

            $this->saveToCache(
                $serial,
                $this->createWholeInform(
                    $instance->getDataGeneratedWithinInform()['inform'],
                    $instance->getDataGeneratedWithinInform()['mikrotikInform']
                )
            );
        } else {
            $serial = $getVirtualSerial[0]['virtualSerial'];
        }

        return $serial;
    }
}
