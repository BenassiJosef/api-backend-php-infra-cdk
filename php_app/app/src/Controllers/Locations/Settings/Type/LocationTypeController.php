<?php
/**
 * Created by jamieaitken on 19/06/2018 at 10:12
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Type;

use App\Models\Locations\Type\LocationTypes;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationTypeController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {

        $send = $this->create($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function create(array $body)
    {
        if (!isset($body['name'], $body['sicCode'])) {
            return Http::status(400, 'MISSING_REQUIRED_PARAMS');
        }

        $doublesNameCheck = $this->em->getRepository(LocationTypes::class)->findOneBy([
            'name' => $body['name']
        ]);

        if (is_object($doublesNameCheck)) {
            return Http::status(409, 'NAME_ALREADY_DEFINED');
        }

        $doublesSicCheck = $this->em->getRepository(LocationTypes::class)->findOneBy([
            'sicCode' => $body['sicCode']
        ]);

        if (is_object($doublesSicCheck)) {
            return Http::status(409, 'SIC_CODE_ALREADY_DEFINED');
        }

        $newLocationType = new LocationTypes($body['name'], $body['sicCode']);
        $this->em->persist($newLocationType);
        $this->em->flush();

        return Http::status(200);
    }

    public function get()
    {
        $types = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationTypes::class, 'u')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $types);
    }
}