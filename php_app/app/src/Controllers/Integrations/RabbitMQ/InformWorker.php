<?php
/**
 * Created by jamieaitken on 08/06/2018 at 09:46
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class InformWorker
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        /**
         * $body has a serial and status(online:offline)
         * Todo: INFORM LOGIC
         */
    }
}