<?php
/**
 * Created by jamieaitken on 16/11/2018 at 14:51
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Locations\LocationSettings;
use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Models\UserData;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

class ConnectionLimitExceededController implements IMultiSiteReport
{
    protected $em;
    protected $cache;

    public function __construct(EntityManager $em)
    {
        $this->em    = $em;
        $this->cache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function getData(array $serial, array $options)
    {

        $cacheCheck = $this->cache->fetch('masterReports:serialsExceedingLimit:' . $options['plan']);
        if (!is_bool($cacheCheck)) {
            return $cacheCheck;
        }


        $yearAgo = new \DateTime();
        $yearAgo->modify('- 2 year');

        $plans = $this->em->createQueryBuilder()
            ->select('
            COUNT(u.id) as totalConnections,
            s.serial,
            l.alias,
            MONTH(u.timestamp) as month,
            YEAR(u.timestamp) as year,
            n.admin,
            n.reseller
            ')
            ->from(Subscriptions::class, 's')
            ->leftJoin(UserData::class, 'u', 'WITH', 's.serial = u.serial')
            ->leftJoin(LocationSettings::class, 'l', 'WITH', 's.serial = l.serial')
            ->leftJoin(NetworkAccess::class, 'n', 'WITH', 'l.serial = n.serial')
            ->where('s.plan_id LIKE :plan')
            ->andWhere('s.status = :active')
            ->andWhere('u.dataUp is NOT NULL')
            ->andWhere('u.timestamp BETWEEN :start AND :end')
            ->setParameter('plan', $options['plan'] . '%')
            ->setParameter('active', 'active')
            ->setParameter('start', $yearAgo)
            ->setParameter('end', new \DateTime())
            ->groupBy('s.serial')
            ->addGroupBy('month')
            ->addGroupBy('year')
            ->having('totalConnections > :planLimit')
            ->setParameter('planLimit', $options['planLimit'])
            ->getQuery()
            ->getArrayResult();


        $response = [];

        foreach ($plans as $plan) {
            if (!isset($response[$plan['serial']])) {
                $response[$plan['serial']] = [
                    'alias'         => $plan['alias'],
                    'timesWentOver' => 0,
                    'admin'         => $plan['admin'],
                    'reseller'      => $plan['reseller'],
                    'timeline'      => []
                ];
            }

            $response[$plan['serial']]['timesWentOver'] += 1;

            $response[$plan['serial']]['timeline'][] = [
                'month'       => $plan['month'],
                'year'        => $plan['year'],
                'connections' => $plan['totalConnections']
            ];
        }

        foreach ($response as $key => $serial) {
            if ($serial['timesWentOver'] <= 3) {
                unset($response[$key]);
            }
        }

        $this->cache->save('masterReports:serialsExceedingLimit:' . $options['plan'], $response);

        return $response;
    }
}