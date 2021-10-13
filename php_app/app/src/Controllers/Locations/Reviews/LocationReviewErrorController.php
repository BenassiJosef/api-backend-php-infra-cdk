<?php
/**
 * Created by jamieaitken on 31/10/2018 at 16:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reviews;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Reviews\LocationReviewErrors;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationReviewErrorController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('id'));

        return $response->withJson($send, $send['status']);
    }

    public function getAllRoute(Request $request, Response $response)
    {

        $send = $this->getAll();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $id)
    {
        $getError = $this->em->createQueryBuilder()
            ->select('u.id, u.serial, l.alias, u.reviewType, u.resource, u.errorCode, u.errorReason, u.createdAt')
            ->from(LocationReviewErrors::class, 'u')
            ->leftJoin(LocationSettings::class, 'l', 'WITH', 'u.serial = l.serial')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        if (empty($getError)) {
            return Http::status(400, 'INVALID_ID');
        }

        return Http::status(200, $getError[0]);
    }

    public function getAll()
    {
        $errors = $this->em->createQueryBuilder()
            ->select('u.id, u.serial, u.reviewType, l.alias')
            ->from(LocationReviewErrors::class, 'u')
            ->leftJoin(LocationSettings::class, 'l', 'WITH', 'u.serial = l.serial')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (empty($errors)) {
            return Http::status(204);
        }

        return Http::status(200, $errors);
    }
}