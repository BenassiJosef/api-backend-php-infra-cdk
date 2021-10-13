<?php
/**
 * Created by jamieaitken on 01/02/2018 at 14:46
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Schedule\LocationSchedule;
use App\Models\Locations\Schedule\LocationScheduleDay;
use App\Models\Locations\Schedule\LocationScheduleTime;
use App\Utils\CacheEngine;
use App\Utils\DayOfWeekFromNumberFormatter;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationScheduleController
{
    protected $em;
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function createOrUpdateRoute(Request $request, Response $response)
    {
        $send = $this->createOrUpdate($request->getParsedBody(), $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getNearestTimezoneRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->isLocationOpen($request->getAttribute('serial'), $body['lat'], $body['long']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    /**
     * @param array $body
     * @param string $serial
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * Update Via LocationTime's Id and dayId
     * Create By Passing in Day Id.
     *
     */

    public function createOrUpdate(array $body, string $serial)
    {
        $getLocation = $this->getScheduleIdBySerial($serial);

        $timeIds = [];
        $dayIds  = [];
        foreach ($body['days'] as $key => $day) {
            if (is_null($day['dayId'])) {
                $scheduleDay = new LocationScheduleDay($day['enabled'], $getLocation, $day['dayNumber']);
                $this->em->persist($scheduleDay);
                $this->em->flush();
                $body['days'][$key]['dayId'] = $scheduleDay->id;
                $dayIds[]                    = $scheduleDay->id;
            } else {
                $this->em->createQueryBuilder()
                    ->update(LocationScheduleDay::class, 'u')
                    ->set('u.enabled', ':dayEnabled')
                    ->where('u.id = :dayId')
                    ->setParameter('dayId', $day['dayId'])
                    ->setParameter('dayEnabled', $day['enabled'])
                    ->getQuery()
                    ->execute();
                $dayIds[] = $day['dayId'];
            }

            foreach ($day['times'] as $k => $time) {
                if (is_null($time['id'])) {
                    $newTimeForLocation        = new LocationScheduleTime($body['days'][$key]['dayId']);
                    $newTimeForLocation->open  = $time['opens'];
                    $newTimeForLocation->close = $time['closes'];
                    $this->em->persist($newTimeForLocation);
                    $this->em->flush();
                    $body['days'][$key]['times'][$k]['id'] = $newTimeForLocation->id;
                    $timeIds[]                             = $newTimeForLocation->id;
                } else {
                    $this->em->createQueryBuilder()
                        ->update(LocationScheduleTime::class, 'u')
                        ->set('u.open', ':open')
                        ->set('u.close', ':close')
                        ->where('u.id = :timeId')
                        ->setParameter('open', $time['opens'])
                        ->setParameter('close', $time['closes'])
                        ->setParameter('timeId', $time['id'])
                        ->getQuery()
                        ->execute();

                    $timeIds[] = $time['id'];
                }
            }
        }

        $this->em->createQueryBuilder()
            ->delete(LocationScheduleTime::class, 'u')
            ->where('u.id NOT IN (:ids)')
            ->andWhere('u.dayId IN (:dayIds)')
            ->setParameter('ids', $timeIds)
            ->setParameter('dayIds', $dayIds)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->update(LocationSchedule::class, 'ls')
            ->set('ls.enabled', ':true')
            ->where('ls.id = :id')
            ->setParameter('true', $body['enabled'])
            ->setParameter('id', $body['scheduleId'])
            ->getQuery()
            ->execute();

        $this->nearlyCache->delete($serial . ':schedule');

        return Http::status(200, $this->get($serial)['message']);
    }

    /**
     * @param string $serial
     * @return array
     */

    public function get(string $serial)
    {

        $structureExistsInCache = $this->nearlyCache->fetch($serial . ':schedule');
        if (!is_bool($structureExistsInCache)) {
            return Http::status(200, $structureExistsInCache);
        }

        $getLocation = $this->getScheduleIdBySerial($serial);

        if (empty($getLocation)) {
            return Http::status(404, 'SCHEDULE_NOT_FOUND');
        }

        $isEnabled = $this->em->createQueryBuilder()
            ->select('u.enabled')
            ->from(LocationSchedule::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $getLocation)
            ->getQuery()
            ->getArrayResult();

        $getScheduleDays = $this->em->createQueryBuilder()
            ->select('u.scheduleId, u.enabled, u.dayNumber, n.id, n.dayId, n.open, n.close')
            ->from(LocationScheduleDay::class, 'u')
            ->join(LocationScheduleTime::class, 'n', 'WITH', 'u.id = n.dayId')
            ->where('u.scheduleId = :n')
            ->setParameter('n', $getLocation)
            ->groupBy('u.dayNumber')
            ->orderBy('u.dayNumber', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $formatDays = [
            'scheduleId' => $getScheduleDays[0]['scheduleId'],
            'enabled'    => $isEnabled[0]['enabled'],
            'days'       => []
        ];

        foreach ($getScheduleDays as $key => $scheduleDay) {
            if (is_null($scheduleDay['dayId'])) {
                continue;
            }
            if (!isset($formatDays['days'][$scheduleDay['dayNumber']])) {
                $formatDays['days'][] = [
                    'dayId'     => $scheduleDay['dayId'],
                    'enabled'   => $scheduleDay['enabled'],
                    'dayNumber' => $scheduleDay['dayNumber'],
                    'dayOfWeek' => LocationScheduleDay::getDayOfWeek($scheduleDay['dayNumber']),
                    'times'     => [
                        [
                            'id'     => $scheduleDay['id'],
                            'opens'  => $scheduleDay['open'],
                            'closes' => $scheduleDay['close']
                        ]
                    ]

                ];
            } else {
                $formatDays['days'][$scheduleDay['dayNumber']]['times'][] = [
                    'id'     => $scheduleDay['id'],
                    'opens'  => $scheduleDay['open'],
                    'closes' => $scheduleDay['close']
                ];
            }
        }

        $this->nearlyCache->save($serial . ':schedule', $formatDays);

        return Http::status(200, $formatDays);
    }

    public function delete()
    {
        return Http::status(200);
    }

    public function isLocationOpen(string $serial, float $latitude = 55.953251, float $longitude = -3.188267)
    {

        $getSchedule = $this->get($serial);

        //if ($getSchedule['status'] === 404) {
        return Http::status(200, ['locationStatus' => 'OPEN']);
        //}

        $schedule = $getSchedule['message'];

        if ($schedule['enabled'] === false) {
            return Http::status(200, ['locationStatus' => 'OPEN']);
        }

        $timeZone = $this->getNearestTimezone($latitude, $longitude);
        $day      = idate('w', $timeZone->getTimestamp());
        $cHour    = (int)$timeZone->format('H') * 60;
        $cMin     = (int)$timeZone->format('i');

        $now = $cHour + $cMin;


        if ($schedule['days'][$day]['enabled'] === false) {
            return Http::status(200, ['locationStatus' => 'OPEN']);
        }

        $sqlQuery = $this->nearlyCache->fetch($serial . ':schedule:' . $day);

        if (is_bool($sqlQuery)) {
            $sqlQuery = $this->em->createQueryBuilder()
                ->select('t')
                ->from(LocationScheduleDay::class, 'u')
                ->join(LocationScheduleTime::class, 't', 'WITH', 'u.id = t.dayId')
                ->where('u.dayNumber = :dayNumber')
                ->andWhere('u.scheduleId = :scheduleId')
                ->andWhere('t.open <= :currentMinutes AND t.close >= :currentMinutes')
                ->setParameter('scheduleId', $schedule['scheduleId'])
                ->setParameter('dayNumber', $day)
                ->setParameter('currentMinutes', $now)
                ->getQuery()
                ->getArrayResult();
        }


        if (!empty($sqlQuery)) {
            return Http::status(200, ['locationStatus' => 'OPEN']);
        }

        array_multisort(array_map(function ($element) {
            return $element['opens'];
        }, $schedule['days'][$day]['times']), SORT_ASC, $schedule['days'][$day]['times']);

        $locationClose = 0;
        $locationOpen  = 0;

        foreach ($schedule['days'][$day]['times'] as $key => $value) {
            if ($now <= $value['opens'] && $now <= $value['closes']
                || $now >= $value['opens'] && $now >= $value['closes']) {
                $locationOpen  = $value['opens'];
                $locationClose = $value['closes'];
            }
        }

        return Http::status(409, [
            'locationStatus' => 'CLOSED',
            'locationOpen'   => $locationOpen,
            'locationClose'  => $locationClose
        ]);
    }

    public function getNearestTimezone(float $latitude, float $longitude)
    {
        $timezoneIds = \DateTimeZone::listIdentifiers();

        if (empty($timezoneIds)) {
            return 'unknown';
        }

        $timeZone   = '';
        $tzDistance = 0;

        //only one identifier?
        if (count($timezoneIds) == 1) {
            $timeZone = $timezoneIds[0];
        } else {
            foreach ($timezoneIds as $timezoneId) {
                $timezone = new \DateTimeZone($timezoneId);
                $location = $timezone->getLocation();
                $tzLat    = $location['latitude'];
                $tzLong   = $location['longitude'];

                $theta    = $longitude - $tzLong;
                $distance = (sin(deg2rad($latitude)) * sin(deg2rad($tzLat)))
                    + (cos(deg2rad($latitude)) * cos(deg2rad($tzLat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = abs(rad2deg($distance));

                if (!$timeZone || $tzDistance > $distance) {
                    $timeZone   = $timezoneId;
                    $tzDistance = $distance;
                }

            }
        }

        return new \DateTime("now", new \DateTimeZone($timeZone));

    }

    private function getScheduleIdBySerial(string $serial)
    {
        return $this->em->createQueryBuilder()
                   ->select('u.schedule')
                   ->from(LocationSettings::class, 'u')
                   ->where('u.serial = :s')
                   ->setParameter('s', $serial)
                   ->getQuery()
                   ->getArrayResult()[0]['schedule'];
    }
}