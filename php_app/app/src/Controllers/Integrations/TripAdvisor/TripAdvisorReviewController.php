<?php
/**
 * Created by jamieaitken on 21/09/2018 at 16:22
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\TripAdvisor;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Position\LocationPosition;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class TripAdvisorReviewController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {

        $send = $this->create($request->getParsedBody(), $request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function create(array $body, string $serial)
    {
        if(!isset($body['url'])){
            return Http::status(400, 'URL_MISSING');
        }

        $getPlaceId = $this->em->createQueryBuilder()
            ->select('u.location')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial=:serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        $this->em->createQueryBuilder()
            ->update(LocationPosition::class, 'u')
            ->set('u.tripadvisorId', ':url')
            ->where('u.id = :location')
            ->setParameter('url', $body['url'])
            ->setParameter('location', $getPlaceId[0]['location'])
            ->getQuery()
            ->execute();

        return Http::status(200);

    }

    public function get(string $serial)
    {
        $tripAdvisorId = $this->em->createQueryBuilder()
            ->select('p.tripadvisorId')
            ->from(LocationSettings::class, 'u')
            ->leftJoin(LocationPosition::class, 'p', 'WITH', 'u.location = p.id')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($tripAdvisorId)) {
            return Http::status(400, 'INVALID_LOCATION');
        }

        return Http::status(200, ['url' => $tripAdvisorId[0]['tripadvisorId']]);
    }
}