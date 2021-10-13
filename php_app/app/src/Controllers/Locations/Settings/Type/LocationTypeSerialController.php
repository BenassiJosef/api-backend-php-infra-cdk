<?php
/**
 * Created by jamieaitken on 19/06/2018 at 10:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Type;

use App\Models\Locations\Type\LocationTypes;
use App\Models\Locations\Type\LocationTypesSerial;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationTypeSerialController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {
        $send = $this->create($request->getAttribute('serial'), $request->getAttribute('locationType'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $send = $this->update($request->getAttribute('serial'), $request->getAttribute('locationType'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $send = $this->delete($request->getAttribute('serial'), $request->getAttribute('locationType'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function create(string $serial, string $locationTypeId)
    {
        $sanityCheck = $this->em->getRepository(LocationTypesSerial::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_object($sanityCheck)) {
            return Http::status(409, 'LOCATION_ALREADY_HAS_TYPE');
        }

        $newLocation = new LocationTypesSerial($locationTypeId, $serial);
        $this->em->persist($newLocation);
        $this->em->flush();

        return Http::status(200, $newLocation->getArrayCopy());
    }

    public function get(string $serial)
    {
        $type = $this->em->createQueryBuilder()
            ->select('u.locationTypeId, a.name')
            ->from(LocationTypesSerial::class, 'u')
            ->leftJoin(LocationTypes::class, 'a', 'WITH', 'u.locationTypeId = a.id')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();
 
        if (empty($type)) {
            $create = $this->create($serial, '0ef9c9d5-e2f7-11e6-b651-040144cf8501');
            return $create;
        }

        return Http::status(200, $type[0]);
    }

    public function update(string $serial, string $locationTypeId)
    {
        $hasType = $this->get($serial);

        if ($hasType['status'] === 204) {
            return $this->create($serial, $locationTypeId);
        }

        $this->em->createQueryBuilder()
            ->update(LocationTypesSerial::class, 'u')
            ->set('u.locationTypeId', ':id')
            ->where('u.serial = :serial')
            ->setParameter('id', $locationTypeId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        return Http::status(200, ['type' => $locationTypeId]);
    }

    public function delete(string $serial, string $locationTypeId)
    {
        $this->em->createQueryBuilder()
            ->delete(LocationTypesSerial::class, 'u')
            ->where('u.locationTypeId = :type')
            ->andWhere('u.serial = :serial')
            ->setParameter('type', $locationTypeId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        return Http::status(200);
    }
}