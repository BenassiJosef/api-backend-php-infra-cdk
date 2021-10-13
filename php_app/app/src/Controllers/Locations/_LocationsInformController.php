<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 11/12/2016
 * Time: 15:01
 */

namespace App\Controllers\Locations;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Locations\Informs\Inform;
use App\Models\Notifications\Notification;
use App\Package\Vendors\Information;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class _LocationsInformController implements iInform
{
    protected $em;
    protected $infrastructureCache;
    protected $vendor;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->vendor = new Information($this->em);
    }

    public function createInform(string $serial = '', string $ip = '', string $vendor = '', array $extraData = [])
    {

        if (strlen($serial) !== 12) {
            return false;
        }

        $getLastInform = $this->getInform($serial);
        $vendorInformation = $this->vendor->getFromKey($vendor);

        $informRequired = false;
        if (is_null($getLastInform)) {

            /** MIGRATE */

            $inform = new Inform($serial, $ip, true, $vendor, $vendorInformation);

            $this->em->persist($inform);
            $this->em->flush();

            $getLastInform = $inform->getArrayCopy();
            /** Generate new network **/
        }
        if (is_array($getLastInform) || is_object($getLastInform)) {
            $now            = new \DateTime();
            $lastInformTime = $now;

            if (is_object($getLastInform['timestamp'])) {
                $lastInformTime = new \DateTime($getLastInform['timestamp']->date);
            }
            if ($getLastInform['status'] === false) {
                $getLastInform['onlineAt'] = $now;
                $mp                        = new _Mixpanel();
                $mp->register('serial', $serial)->track('location_online', $getLastInform);

                $deviceFlicker = ($now > $lastInformTime->modify('+12 minutes'));

                if ($deviceFlicker) {
                    //$informController->mailChange($serial, 1, $ip, $oldInform['timestamp']);
                }
                $informRequired = true;
            }

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

            if ($getLastInform['status'] === false) {

                $client = new QueueSender();
                $client->sendMessage([
                    'site'   => $serial,
                    'status' => 'online'
                ], QueueUrls::INFORM);

                $client->sendMessage(
                    [
                        'notificationContent' => [
                            'objectId' => $serial,
                            'title'    => 'Location Online',
                            'kind'     => 'network_online',
                            'link'     => '/status-check',
                            'serial'   => $serial
                        ]
                    ],
                    QueueUrls::NOTIFICATION
                );
            }

            $shouldInform = ($now > $lastInformTime->modify('+5 minutes'));

            $dataToSend = [
                'ip'        => $ip,
                'status'    => true,
                'vendor'    => $vendor,
                'timestamp' => $now,
                'onlineAt'  => $getLastInform['onlineAt'],
                'offlineAt' => $getLastInform['offlineAt'],
                'createdAt' => $getLastInform['createdAt']
            ];

            $this->saveToCache($serial, $dataToSend);

            if ($shouldInform || $informRequired === true) {
                return $this->setInform($serial, $dataToSend);
            }


            return '';
        }

        return false;
    }

    public function setInform(string $serial = '', array $arr = [])
    {
        $inform = $this->saveToPersistentStorage($serial, $arr);

        return (array) $inform;
    }

    public function getInform(string $serial = '')
    {
        $inform = $this->getFromCache($serial);

        if ($inform !== false) {
            return $inform;
        }

        return $this->getFromPersistentStorage($serial);
    }

    public function saveToCache(string $serial = '', array $arr = [])
    {
        return $this->infrastructureCache->save('informs:' . $serial, $arr);
    }

    public function getFromCache(string $serial = '')
    {
        return $this->infrastructureCache->fetch('informs:' . $serial);
    }

    public function saveToPersistentStorage(string $serial, array $arr = [])
    {
        $locationExists = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);

        if (!is_null($locationExists)) {
            $locationExists->timestamp = $arr['timestamp'];
            $locationExists->ip        = $arr['ip'];
            $locationExists->status    = $arr['status'];
            $locationExists->vendor    = $arr['vendor'];
            $this->em->persist($locationExists);
            $this->em->flush();

            $this->saveToCache($serial, $locationExists->getArrayCopy());

            return $locationExists->getArrayCopy();
        }

        return null;
    }

    public function getFromPersistentStorage(string $serial)
    {
        $inform = $this->em->getRepository(Inform::class)->findOneBy([
            'serial' => $serial
        ]);

        if (!is_null($inform)) {
            $informArray = $inform->getArrayCopy();
            $this->saveToCache($serial, $informArray);

            return $informArray;
        }

        return null;
    }
}
