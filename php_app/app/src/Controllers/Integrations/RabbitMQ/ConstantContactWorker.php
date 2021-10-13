<?php
/**
 * Created by jamieaitken on 09/10/2018 at 11:16
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\ConstantContact\ConstantContactController;
use App\Models\Integrations\ConstantContact\ConstantContactListLocation;
use App\Models\Integrations\ConstantContact\ConstantContactUserDetails;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Utils\CacheEngine;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;


class ConstantContactWorker
{
    private $logger;
    private $em;
    private $constantContactController;

    public function __construct(Logger $logger, EntityManager $em, ConstantContactController $constantContactController)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->constantContactController = $constantContactController;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {

        $cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));

        $textLocalDetails = $cache->fetch('constantContact:' . $body['serial']);

        if (is_bool($textLocalDetails)) {

            $textLocalDetails = $this->em->createQueryBuilder()
                ->select('u.accessToken, p.contactListId, p.filterListId')
                ->from(ConstantContactUserDetails::class, 'u')
                ->leftJoin(ConstantContactListLocation::class, 'p', 'WITH', 'u.id = p.detailsId')
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

            $cache->save('constantContact:' . $body['serial'], $textLocalDetails);

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

        if (!isset($body['user']['phone'])) {
            return false;
        }

        if (empty($body['user']['phone'])) {
            return false;
        }

        $response = $this->constantContactController->createContact($body['user'], $textLocalDetails);

        return true;
    }
}