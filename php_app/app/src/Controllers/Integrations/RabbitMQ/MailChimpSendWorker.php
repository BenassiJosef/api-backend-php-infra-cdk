<?php
/**
 * Created by jamieaitken on 28/09/2018 at 15:26
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\MailChimp\MailChimpContactController;
use App\Models\Integrations\MailChimp\MailChimpContactListLocation;
use App\Models\Integrations\MailChimp\MailChimpUserDetails;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\DataSources\OptInService;
use App\Utils\CacheEngine;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;

class MailChimpSendWorker
{
    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var MailChimpContactController
     */
    private $mailChimpContactController;

    /**
     * @var OptInService
     */
    private $optInService;

    public function __construct(
        Logger $logger,
        EntityManager $em,
        MailChimpContactController $mailChimpContactController,
        ?OptInService $optInService = null
    ) {
        $this->logger                     = $logger;
        $this->em                         = $em;
        $this->mailChimpContactController = $mailChimpContactController;
        if ($optInService === null) {
            $optInService = new OptInService($em);
        }
        $this->optInService = $optInService;
    }


    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $cache     = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));

        $serial    = $body['serial'];
        $profileId = $body['user']['id'];

        if ($profileId === null) {
            newrelic_add_custom_parameter('mailchimp-body', json_encode($body));
        }
        $textLocalDetails = $cache->fetch('mailChimp:' . $body['serial']);
        if (is_bool($textLocalDetails)) {
            $textLocalDetails = $this->em->createQueryBuilder()
                                         ->select('u.apiKey, p.contactListId, p.filterListId')
                                         ->from(MailChimpUserDetails::class, 'u')
                                         ->leftJoin(
                                             MailChimpContactListLocation::class,
                                             'p',
                                             'WITH',
                                             'u.id = p.detailsId'
                                         )
                                         ->where('p.serial = :serial')
                                         ->andWhere('p.enabled = :true')
                                         ->andWhere('p.onEvent = :event')
                                         ->setParameter('serial', $serial)
                                         ->setParameter('true', true)
                                         ->setParameter('event', $body['event'])
                                         ->getQuery()
                                         ->getArrayResult();

            if (empty($textLocalDetails)) {
                return false;
            }

            $cache->save('mailChimp:' . $body['serial'], $textLocalDetails);
        }

        if (!$this->optInService->dataOptInForLocationWithIds($serial, $profileId)) {
            return false;
        }

        if (!isset($body['user']['email'])) {
            return false;
        }

        if (empty($body['user']['email'])) {
            return false;
        }
        $this->mailChimpContactController->createContact($body['user'], $textLocalDetails);
        return true;
    }
}