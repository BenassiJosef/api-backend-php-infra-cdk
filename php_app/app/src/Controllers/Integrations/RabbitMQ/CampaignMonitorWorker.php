<?php
/**
 * Created by jamieaitken on 10/10/2018 at 11:32
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactController;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorListLocation;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorUserDetails;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Utils\CacheEngine;
use Aws\Sqs\SqsClient;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class CampaignMonitorWorker
{
    private $em;
    private $logger;
    private $campaignMonitorContactController;

    public function __construct(Logger $logger, EntityManager $em, CampaignMonitorContactController $campaignMonitorContactController)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->campaignMonitorContactController = $campaignMonitorContactController;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));

        $textLocalDetails = $cache->fetch('campaignMonitor:' . $body['serial']);

        if (is_bool($textLocalDetails)) {
            $textLocalDetails = $this->em->createQueryBuilder()
                ->select('u.apiKey, p.contactListId, p.filterListId')
                ->from(CampaignMonitorUserDetails::class, 'u')
                ->leftJoin(CampaignMonitorListLocation::class, 'p', 'WITH', 'u.id = p.detailsId')
                ->where('p.serial = :serial')
                ->andWhere('p.enabled = :true')
                ->andWhere('p.onEvent = :event')
                ->setParameter('serial', $body['serial'])
                ->setParameter('true', true)
                ->setParameter('event', $body['event'])
                ->getQuery()
                ->getArrayResult();

            if (empty($textLocalDetails)) {
                return false;
            }

            $cache->save('campaignMonitor:' . $body['serial'], $textLocalDetails);
        }


        $dataOptOutCheck = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(LocationOptOut::class, 'u')
            ->where('u.profileId = :profileId')
            ->andWhere('u.serial = :serial')
            ->andWhere('u.deleted = :false')
            ->setParameter('profileId', $body['user']['id'])
            ->setParameter('serial', $body['serial'])
            ->setParameter('false', true)
            ->getQuery()
            ->getArrayResult();

        if (empty($dataOptOutCheck)) {
            return false;
        }

        $marketingOptOutCheck = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(MarketingOptOut::class, 'u')
            ->where('u.uid = :profileId')
            ->andWhere('u.serial = :serial')
            ->andWhere('u.optOut = :true')
            ->setParameter('profileId', $body['user']['id'])
            ->setParameter('serial', $body['serial'])
            ->setParameter('true', false)
            ->getQuery()
            ->getArrayResult();

        if (empty($marketingOptOutCheck)) {
            return false;
        }

        if (!isset($body['user']['email'])) {
            return false;
        }

        if (empty($body['user']['email'])) {
            return false;
        }

        $response = $this->campaignMonitorContactController->createContact($body['user'], $textLocalDetails);

        return true;
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        return $response->withJson($send, $send['status']);
    }

    public function create()
    {
        return Http::status(200);
    }

    public function get()
    {
        return Http::status(200);
    }

    public function update()
    {
        return Http::status(200);
    }

    public function delete()
    {
        return Http::status(200);
    }
}