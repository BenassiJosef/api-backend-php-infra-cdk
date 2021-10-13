<?php
/**
 * Created by jamieaitken on 2019-07-04 at 11:56
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;


use App\Controllers\Integrations\Airship\AirshipContactController;
use App\Models\Integrations\Airship\AirshipListLocation;
use App\Models\Integrations\Airship\AirshipUserDetails;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\DataSources\OptInService;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AirshipWorker
 * @package App\Controllers\Integrations\RabbitMQ
 */
class AirshipWorker
{
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var AirshipContactController $airshipController
     */
    private $airshipController;

    /**
     * @var OptInService
     */
    private $optInService;

    /**
     * AirshipWorker constructor.
     * @param Logger $logger
     * @param EntityManager $em
     * @param AirshipContactController $airshipController
     * @param OptInService $optInService
     */
    public function __construct(
        Logger $logger,
        EntityManager $em,
        AirshipContactController $airshipController,
        ?OptInService $optInService = null
    ) {
        if ($optInService === null) {
            $optInService = new OptInService($em);
        }
        $this->logger            = $logger;
        $this->em                = $em;
        $this->airshipController = $airshipController;
        $this->optInService      = $optInService;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    /**
     * @param array $body
     * @return bool
     * @throws Exception
     */
    public function runWorker(array $body)
    {
        $cache          = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $serial         = $body['serial'];
        $profileId      = $body['user']['id'];
        $airshipDetails = $cache->fetch('airship:' . $serial);

        if (is_bool($airshipDetails)) {
            $airshipDetails = $this->em->createQueryBuilder()
                                       ->select('u.apiKey, p.contactListId, p.filterListId')
                                       ->from(AirshipUserDetails::class, 'u')
                                       ->leftJoin(AirshipListLocation::class, 'p', 'WITH', 'u.id = p.detailsId')
                                       ->where('p.serial = :serial')
                                       ->andWhere('p.enabled = :true')
                                       ->andWhere('p.onEvent = :event')
                                       ->setParameter('serial', $serial)
                                       ->setParameter('true', true)
                                       ->setParameter('event', $body['event'])
                                       ->getQuery()
                                       ->getArrayResult();

            if (empty($airshipDetails)) {
                return false;
            }

            $cache->save('airship:' . $serial, $airshipDetails);

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

        $response = $this->airshipController->createContact($body['user'], $airshipDetails);

        return true;
    }
}