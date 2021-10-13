<?php
/**
 * Created by jamieaitken on 11/02/2018 at 00:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\PredictiveReports;

use App\Models\UserData;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Phpml\Regression\LeastSquares;
use Slim\Http\Response;
use Slim\Http\Request;

class PredictConnectionsReportController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {

        $send = $this->create();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $start = new \DateTime();
        $end   = new \DateTime();
        $start->setTimestamp(1515628800);
        $end->setTimestamp(1518479999);
        $send = $this->get($request->getAttribute('serial'), $start, $end, [
            'grouping' => [
                'group' => 'months'
            ],
            'sort'     => 'DESC'
        ]);

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        return $response->withJson($send, $send['status']);
    }

    public function create()
    {
        return Http::status(200);
    }

    public function get(string $serial, $start, $end, $options)
    {
        $sql = '
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload, 
        COUNT(ud.profileId) as totalConnections, 
        COUNT(DISTINCT(ud.profileId)) as uniqueConnections,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
        COALESCE(AVG(ud.dataUp),0) as averageUp,
        COALESCE(AVG(ud.dataDown),0) as averageDown, 
        AVG(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as averageUptime,
        UNIX_TIMESTAMP(ud.timestamp) as timestamp';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day, HOUR(ud.timestamp) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month, DAY(ud.timestamp) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(ud.timestamp) as year, WEEK(ud.timestamp) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(ud.timestamp) as year, MONTH(ud.timestamp) as month';
            $group = 'year, month';
        }

        $totals = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.dataUp IS NOT NULL')
            ->andWhere('ud.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('serial', $serial)
            ->groupBy($group)
            ->orderBy('ud.timestamp', $options['sort'])
            ->getQuery()
            ->getArrayResult();

        $regression = new LeastSquares();
        $regression->train($totals, [3.1]);

        $regression->predict([64]);

        var_dump($regression);

        return Http::status(200);
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