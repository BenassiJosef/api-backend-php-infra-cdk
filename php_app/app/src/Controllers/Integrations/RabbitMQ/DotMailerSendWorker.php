<?php
/**
 * Created by jamieaitken on 01/10/2018 at 16:21
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\dotMailer\DotMailerContactController;
use App\Models\Integrations\DotMailer\DotMailerAddressLocation;
use App\Models\Integrations\DotMailer\DotMailerUserDetails;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Utils\CacheEngine;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;

class DotMailerSendWorker
{
    private $logger;
    private $em;
    private $dotMailerContactController;

    public function __construct(Logger $logger, EntityManager $em, DotMailerContactController $dotMailerContactController)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->dotMailerContactController = $dotMailerContactController;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {

        $cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));

        $textLocalDetails = $cache->fetch('dotMailer:' . $body['serial']);

        if (is_bool($textLocalDetails)) {

            $textLocalDetails = $this->em->createQueryBuilder()
                ->select('u.apiKey, p.contactListId, p.filterListId')
                ->from(DotMailerUserDetails::class, 'u')
                ->leftJoin(DotMailerAddressLocation::class, 'p', 'WITH', 'u.id = p.detailsId')
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

            $cache->save('dotMailer:' . $body['serial'], $textLocalDetails);
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

        $response = $this->dotMailerContactController->createContact($body['user'], $textLocalDetails);

        return true;
    }
}