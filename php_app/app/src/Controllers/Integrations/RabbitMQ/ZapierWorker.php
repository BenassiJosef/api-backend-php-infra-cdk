<?php

/**
 * Created by jamieaitken on 11/06/2018 at 11:46
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Locations\Reports\_RegistrationReportController;
use App\Models\Integrations\Hooks\Hook;
use App\Models\Locations\LocationOptOut;
use App\Package\DataSources\OptInService;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class ZapierWorker
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var OptInService
     */
    private $optInService;


    public function __construct(
        EntityManager $em,
        ?OptInService $optInService = null
    ) {
        $this->entityManager = $em;
        if ($optInService === null) {
            $optInService = new OptInService($em);
        }
        $this->optInService = $optInService;
    }


    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->entityManager->clear();
    }

    public function runWorker(array $payload)
    {
        $infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $hookController      = new _HooksController($this->entityManager);


        $serial    = $payload['serial'];
        $profileId = $payload['payload']['id'];
        if ($payload['event'] === 'connection') {
            $profileId = $payload['payload']['profile_id'];
        }
        if (!$this->optInService->dataOptInForLocationWithIds($serial, $profileId)) {
            return false;
        }

        $hook = $infrastructureCache->fetch('hooks:' . $payload['serial'] . ':' . $payload['event']);

        if (is_array($hook)) {
            if (array_key_exists('error', $hook)) {
                return Http::status(204, ['error' => 'NO_HOOK_FOUND', 'body' => $hook]);
            }
        }

        if ($hook === false) {
            $hook = $this->entityManager->getRepository(Hook::class)->findBy(
                [
                    'param'   => $payload['serial'],
                    'event'   => $payload['event'],
                    'deleted' => false
                ]
            );
        }

        if (is_array($hook)) {
            $infrastructureCache->save('hooks:' . $payload['serial'] . ':' . $payload['event'], $hook);
            $count = 0;
            foreach ($hook as $event) {
                $count++;
                if ($payload['event'] === 'registration_unvalidated' || $payload['event'] === 'registration_validated') {
                    if (isset($payload['payload']['custom'][$payload['serial']])) {
                        $newRegistrationReportController = new _RegistrationReportController($this->entityManager);
                        $customQuestions                 = $newRegistrationReportController->fetchHeadersForSerial($payload['serial']);
                        foreach ($customQuestions as $key => $customQuestion) {
                            if (!isset($customQuestion['id'])) {
                                continue;
                            }


                            foreach ($payload['payload']['custom'][$payload['serial']] as $qK => $item) {
                                if ($qK === $customQuestion['id']) {
                                    $payload[$customQuestion['id']] = $item;
                                }
                            }
                        }
                    }
                }
                $hookController->send($event->target_url, $payload['payload']);
            }
            if ($count === sizeof($hook)) {
                return Http::status(200, $count);
            } elseif ($count > 0 && $count < sizeof($hook)) {
                return Http::status(206, 'PARTIAL');
            } else {
                return Http::status(204, 'FAILED');
            }
        }

        $infrastructureCache->save(
            'hooks:' . $payload['serial'] . ':' . $payload['event'],
            ['error' => 'NO_HOOK_FOUND']
        );

        return Http::status(204, ['error' => 'NO_HOOK_FOUND', 'body' => $hook]);
    }
}
