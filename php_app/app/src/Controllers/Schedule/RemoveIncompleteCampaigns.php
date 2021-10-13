<?php
/**
 * Created by jamieaitken on 19/03/2018 at 17:42
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Schedule;

use App\Models\MarketingCampaigns;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;


class RemoveIncompleteCampaigns
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function runRoute(Request $request, Response $response)
    {

        $send = $this->run();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }


    public function run()
    {
        $this->em->createQueryBuilder()
            ->delete(MarketingCampaigns::class, 'u')
            ->where('u.name IS NULL')
            ->andWhere('u.eventId IS NULL')
            ->andWhere('u.messageId IS NULL')
            ->getQuery()
            ->execute();

        return Http::status(200);
    }
}