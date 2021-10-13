<?php

/**
 * Created by jamieaitken on 06/02/2018 at 16:20
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Position;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use PDO;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationPositionController
{
    const CLOSEST_QUERY = 'SELECT ls.alias, ls.organization_id, ns.latitude, ns.longitude, ls.serial, 111.045 * DEGREES(ACOS(COS(RADIANS(:lat))
 * COS(RADIANS(ns.latitude))
 * COS(RADIANS(ns.longitude) - RADIANS(:lng))
 + SIN(RADIANS(:lat))
 * SIN(RADIANS(ns.latitude))))
 AS distance_in_km
FROM network_settings_location ns
LEFT JOIN location_settings ls ON ls.location = ns.id
WHERE ls.organization_id IS NOT NULL
HAVING distance_in_km < :distance
ORDER BY distance_in_km ASC
LIMIT 0,:limitVenues';

    const CLOSEST_QUERY_LOYALTY = 'SELECT ls.alias, ls.organization_id, ns.latitude, ns.longitude, ls.serial, 111.045 * DEGREES(ACOS(COS(RADIANS(:lat))
 * COS(RADIANS(ns.latitude))
 * COS(RADIANS(ns.longitude) - RADIANS(:lng))
 + SIN(RADIANS(:lat))
 * SIN(RADIANS(ns.latitude))))
 AS distance_in_km,
 lss.id as scheme_id,
 lss.icon as icon,
 lss.background_colour,
 lss.required_stamps,
 lr.name
FROM network_settings_location ns
LEFT JOIN location_settings ls ON ls.location = ns.id
LEFT JOIN loyalty_stamp_scheme lsss ON lsss.organization_id = ls.organization_id AND lsss.serial = ls.serial AND lsss.is_active = 1
LEFT JOIN loyalty_stamp_scheme lss ON lss.organization_id = ls.organization_id AND lss.is_active = 1
LEFT JOIN loyalty_reward lr ON lr.id = lss.reward_id
WHERE lsss.organization_id IS NOT NULL OR (lss.organization_id IS NOT NULL AND lss.serial IS NULL)
AND ls.alias IS NOT NULL

GROUP BY ls.serial
HAVING distance_in_km < :distance
ORDER BY distance_in_km ASC
LIMIT 0,:limitVenues';

    protected $em;
    private $immutableKeys = [
        'id',
        'updatedAt'
    ];
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function getClosestLocations(Request $request, Response $response)
    {
        $lat = (float) $request->getQueryParam('lat', null);
        $lng = (float) $request->getQueryParam('lng', null);
        $distance = (float) $request->getQueryParam('distance', 0.5);
        $limit = (int) $request->getQueryParam('limit', 5);
        $conn  = $this->em->getConnection();

        $query = $conn->prepare(self::CLOSEST_QUERY);

        $query->bindParam('lat', $lat);
        $query->bindParam('lng', $lng);
        $query->bindParam('distance', $distance);
        $query->bindParam('limitVenues', $limit, PDO::PARAM_INT);
        $query->execute();

        return $response->withJson($query->fetchAll());
    }

    public function getClosestLoyaltyLocations(Request $request, Response $response)
    {
        $lat = (float) $request->getQueryParam('lat', null);
        $lng = (float) $request->getQueryParam('lng', null);
        $distance = (float) $request->getQueryParam('distance', 1.5);
        $limit = (int) $request->getQueryParam('limit', 15);
        $conn  = $this->em->getConnection();

        $query = $conn->prepare(self::CLOSEST_QUERY_LOYALTY);

        $query->bindParam('lat', $lat);
        $query->bindParam('lng', $lng);
        $query->bindParam('distance', $distance);
        $query->bindParam('limitVenues', $limit, PDO::PARAM_INT);
        $query->execute();

        return $response->withJson($query->fetchAll());
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $serial)
    {
        $location = $this->getPositionIdFromSerial($serial);

        $getLocationData = $this->em->getRepository(LocationPosition::class)->findOneBy([
            'id' => $location[0]['location']
        ]);

        if (is_null($getLocationData)) {
            return Http::status(404, 'CAN_NOT_LOCATE_LOCATION_DATA');
        }

        return Http::status(200, $getLocationData->getArrayCopy());
    }

    public function update(string $serial, array $body)
    {
        $location = $this->getPositionIdFromSerial($serial);

        $getLocationData = $this->em->getRepository(LocationPosition::class)->findOneBy([
            'id' => $location[0]['location']
        ]);

        if (is_null($getLocationData)) {
            return Http::status(404, 'CAN_NOT_LOCATE_LOCATION_DATA');
        }

        $arrayKeys = array_keys($getLocationData->getArrayCopy());

        foreach ($body as $key => $value) {
            if (!in_array($key, $this->immutableKeys) && in_array($key, $arrayKeys)) {
                $getLocationData->$key = $value;
            }
        }

        $getLocationData->updatedAt = new \DateTime();
        $this->em->persist($getLocationData);
        $this->em->flush();

        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setHeader('x-api-key', 'XOAwN1bXMp78ecbjqGPYX1vrSBRJk0VA2RHScA3P');
        $curl->post('https://firebase.blackbx.io/address', [
            'payload'  => $getLocationData->getArrayCopy(),
            'document' => $getLocationData->formattedAddress
        ]);

        $this->nearlyCache->delete($serial . ':location');

        $sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        $sitesTemplate = [$serial . ':location'];

        foreach ($sitesUsingThisAsTemplate as $key => $site) {
            $sitesTemplate[] = $site['serial'] . ':location';
        }

        $this->nearlyCache->deleteMultiple($sitesTemplate);

        return Http::status(200, $body);
    }

    private function getPositionIdFromSerial(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.location')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult();
    }

    public function getNearlyLocation(string $serial, string $locationId)
    {
        $exists = $this->nearlyCache->fetch($serial . ':location');
        if (!is_bool($exists)) {
            return Http::status(200, $exists);
        }

        $location = $this->em->createQueryBuilder()
            ->select('u.latitude, u.longitude')
            ->from(LocationPosition::class, 'u')
            ->where('u.id = :i')
            ->setParameter('i', $locationId)
            ->getQuery()
            ->getArrayResult()[0];

        $this->nearlyCache->save($serial . ':location', $location);

        return Http::status(200, $location);
    }

    public static function defaultPosition()
    {
        return new LocationPosition(
            LocationPosition::defaultLat(),
            LocationPosition::defaultLng(),
            LocationPosition::defaultFormattedAddress(),
            LocationPosition::defaultPostCode(),
            LocationPosition::defaultRoute(),
            LocationPosition::defaultPostalTown(),
            LocationPosition::defaultAreaLevel2(),
            LocationPosition::defaultAreaLevel1(),
            LocationPosition::defaultCountry()
        );
    }
}
