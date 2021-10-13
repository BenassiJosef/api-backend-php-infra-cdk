<?php
/**
 * Created by jamieaitken on 05/12/2017 at 16:24
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Notifications;

use App\Models\Notifications\Changelog;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ChangelogController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {

        $send = $this->create();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $send = $this->update();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function create()
    {
        return Http::status(200);
    }

    public function get()
    {
        $logs = $this->em->createQueryBuilder()
            ->select('u')
            ->from(Changelog::class, 'u')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $logs);
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