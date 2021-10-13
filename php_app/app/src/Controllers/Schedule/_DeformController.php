<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/03/2017
 * Time: 16:33
 */

namespace App\Controllers\Schedule;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\Notifications\Notification;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\PushNotifications;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _DeformController
{
    protected $em;
    protected $infrastructureCache;
    private $client;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->deform();

        $this->em->clear();

        return $response->withJson($send);
    }

    public function deform()
    {
        $informs = $this->em->getRepository(Inform::class)->findBy([
            'status' => true,
            'vendor' => 'MIKROTIK'
        ]);
        $toSend  = [];
        foreach ($informs as $inform) {
            $fetcher = $this->infrastructureCache->fetch('informs:' . $inform->serial);
            if ($fetcher === false) {
                /** NETWORK IS OFFLINE AS NO CACHE VALUE EXISTS */
                if ($this->beforeNow($inform->timestamp)) {
                    $inform->status = false;
                }
            } else {
                $oldTime = $fetcher['timestamp'];
                if (!is_object($oldTime)) {
                    $oldTime = new \DateTime($fetcher['timestamp']);
                }
                if ($this->beforeNow($oldTime)) {
                    $inform->status = false;
                }
            }
            if ($inform->status === false) {
                $toSend[] = $inform->getArrayCopy();

                $this->setOffline($inform);
            }
        }

        return Http::status(200, $toSend);
    }

    public function beforeNow(\DateTime $date)
    {
        $now = new \DateTime();

        return $now > $date->modify('+20 minutes');
    }

    public function setOffline($inform)
    {
        $informMaster = $this->em->getRepository(MikrotikInform::class)->findOneBy([
            'informId' => $inform->id
        ]);

        if (is_object($informMaster)) {
            if (!is_null($informMaster->masterSite)) {
                return false;
            }
            if ($informMaster->master === true) {
                $slaves = $this->em->getRepository(MikrotikInform::class)->findBy([
                    'masterSite' => $inform->serial
                ]);
                foreach ($slaves as $slave) {
                    $informModel = $this->em->getRepository(Inform::class)->findOneBy([
                        'id' => $slave->informId
                    ]);
                    $this->persistOffline($informModel);
                }
            }
        }

        return $this->persistOffline($inform);
    }

    public function persistOffline($inform)
    {
        $now = new \DateTime();

        $inform->status    = false;
        $inform->offlineAt = $now;
        $this->em->persist($inform);
        $this->em->flush();

        $this->infrastructureCache->delete('informs:' . $inform->serial);

        $this->client = new QueueSender();

        $this->client->sendMessage([
            'site'   => $inform->serial,
            'status' => 'offline',
        ], QueueUrls::INFORM);


        $this->client->sendMessage(
            [
                'notificationContent' => [
                    'objectId' => $inform->serial,
                    'title'    => 'Location Offline',
                    'kind'     => 'network_offline',
                    'link'     => '/status-check',
                    'serial'   => $inform->serial
                ]
            ],
            QueueUrls::NOTIFICATION
        );

        $mp = new _Mixpanel();
        $mp->register('serial', $inform->serial)->track('location_offline', $inform->getArrayCopy());

        return true;
    }
}
