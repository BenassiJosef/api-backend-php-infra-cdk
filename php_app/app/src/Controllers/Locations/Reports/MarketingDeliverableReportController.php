<?php
/**
 * Created by jamieaitken on 2019-06-27 at 11:55
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports;


use App\Models\Marketing\MarketingDeliverable;
use App\Models\Marketing\MarketingDeliverableEvent;
use App\Models\UserProfile;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MarketingDeliverableReportController extends ReportController implements IReport
{

    private $defaultOrder = 'me.timestamp';

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $maxResults = 50;

        $marketingDeliverables = $this->em->createQueryBuilder()
            ->select('
                m.type,
                m.profileId,
                m.type,
                m.id,
                me.timestamp,
                me.event,
                me.eventSpecificInfo,
                up.email
            ')->from(MarketingDeliverable::class, 'm')
            ->leftJoin(MarketingDeliverableEvent::class, 'me', 'WITH', 'm.id = me.marketingDeliverableId')
            ->join(UserProfile::class, 'up', 'WITH', 'm.profileId = up.id')
            ->where('m.serial IN (:serial)')
            ->andWhere('m.createdAt BETWEEN :now AND :end');
        if (isset($options['campaignId'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.campaignId = :campaignId')
                ->setParameter('campaignId', $options['campaignId']);
        }

        if (isset($options['type'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.templateType = :type')
                ->setParameter('type', $options['type']);
        }

        if (isset($options['eventType'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('me.event = :eventType')
                ->setParameter('eventType', $options['eventType']);
        }

        $marketingDeliverables = $marketingDeliverables
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->setFirstResult($options['offset'])
            ->setMaxResults($maxResults);

        $results = new Paginator($marketingDeliverables);
        $results->setUseOutputWalkers(false);

        $marketingDeliverables = $results->getIterator()->getArrayCopy();

        if (empty($marketingDeliverables)) {
            return [];
        }

        $response = [];

        foreach ($marketingDeliverables as $deliverableKey => $deliverable) {

            $key = array_search($deliverable['id'], array_column($response, 'id'));

            if (is_bool($key)) {
                $response[] = [
                    'profileId' => $deliverable['profileId'],
                    'email'     => $deliverable['email'],
                    'type'      => $deliverable['type'],
                    'id'        => $deliverable['id'],
                    'events'    => []
                ];


            }

            $key = array_search($deliverable['id'], array_column($response, 'id'));

            $response[$key]['events'][] = [
                'event'             => $deliverable['event'],
                'eventSpecificInfo' => $deliverable['eventSpecificInfo'],
                'timestamp'         => $deliverable['timestamp']
            ];

        }

        return [
            'table'       => $response,
            'has_more'    => false,
            'total'       => count($marketingDeliverables),
            'next_offset' => $options['offset'] + $maxResults
        ];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {

        $sql = '
        me.timestamp as timestamp,
        me.event,
        COUNT (m.id) as events';

        if ($options['grouping']['group'] === 'hours') {
            $sql   .= ', YEAR(FROM_UNIXTIME(me.timestamp)) as year, MONTH(FROM_UNIXTIME(me.timestamp)) as month, DAY(FROM_UNIXTIME(me.timestamp)) as day, HOUR(FROM_UNIXTIME(me.timestamp)) as hour';
            $group = 'year, month, day, hour';
        } elseif ($options['grouping']['group'] === 'days') {
            $sql   .= ', YEAR(FROM_UNIXTIME(me.timestamp)) as year, MONTH(FROM_UNIXTIME(me.timestamp)) as month, DAY(FROM_UNIXTIME(me.timestamp)) as day';
            $group = 'year, month, day';
        } elseif ($options['grouping']['group'] === 'weeks') {
            $sql   .= ', YEAR(FROM_UNIXTIME(me.timestamp)) as year, WEEK(FROM_UNIXTIME(me.timestamp)) as week';
            $group = 'year, week';
        } elseif ($options['grouping']['group'] === 'months') {
            $sql   .= ', YEAR(FROM_UNIXTIME(me.timestamp)) as year, MONTH(FROM_UNIXTIME(me.timestamp)) as month';
            $group = 'year, month';
        }


        $marketingDeliverables = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(MarketingDeliverable::class, 'm')
            ->leftJoin(MarketingDeliverableEvent::class, 'me', 'WITH', 'm.id = me.marketingDeliverableId')
            ->where('m.serial IN (:serial)')
            ->andWhere('m.createdAt BETWEEN :now AND :end');
        if (isset($options['campaignId'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.campaignId = :campaignId')
                ->setParameter('campaignId', $options['campaignId']);
        }

        if (isset($options['type'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.templateType = :type')
                ->setParameter('type', $options['type']);
        }

        if (isset($options['eventType'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('me.event = :eventType')
                ->setParameter('eventType', $options['eventType']);
        }

        $marketingDeliverables = $marketingDeliverables
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->groupBy($group)
            ->orderBy($this->defaultOrder, $options['sort'])
            ->getQuery()
            ->getArrayResult();

        if (empty($marketingDeliverables)) {
            return [];
        }

        foreach ($marketingDeliverables as $key => $registration) {
            foreach ($registration as $k => $v) {
                if (is_numeric($v)) {
                    $marketingDeliverables[$key][$k] = (int)round($v);
                }
            }
        }

        return $marketingDeliverables;

    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $marketingDeliverables = $this->em->createQueryBuilder()
            ->select(
                'COUNT (m.id) as events, me.event'
            )
            ->from(MarketingDeliverable::class, 'm')
            ->leftJoin(MarketingDeliverableEvent::class, 'me', 'WITH', 'm.id = me.marketingDeliverableId')
            ->where('m.serial IN (:serial)')
            ->andWhere('m.createdAt BETWEEN :now AND :end');

        if (isset($options['campaignId'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.campaignId = :campaignId')
                ->setParameter('campaignId', $options['campaignId']);
        }

        if (isset($options['type'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('m.templateType = :type')
                ->setParameter('type', $options['type']);
        }

        if (isset($options['eventType'])) {
            $marketingDeliverables = $marketingDeliverables->andWhere('me.event = :eventType')
                ->setParameter('eventType', $options['eventType']);

            if ($options['eventType'] === 'validation') {
                $marketingDeliverables = $marketingDeliverables->andWhere('me.eventSpecificInfo LIKE :clickLink')
                    ->setParameter('clickLink', 'https://my.stampede.ai/validation/' . '%');
            }
        }

        $marketingDeliverables = $marketingDeliverables
            ->setParameter('serial', $serial)
            ->setParameter('now', $start)
            ->setParameter('end', $end)
            ->groupBy('me.event')
            ->getQuery()
            ->getArrayResult();

        if (empty($marketingDeliverables)) {
            return [];
        }


        $response = [
            'delivered' => 0,
            'bounce'    => 0,
            'deferred'  => 0,
            'click'     => 0,
            'processed' => 0
        ];


        foreach ($marketingDeliverables as $key => $marketingDeliverable) {
            if (isset($response[$marketingDeliverable['event']])) {
                if (is_numeric($marketingDeliverable['events'])) {
                    $response[$marketingDeliverable['event']] = (int)round($marketingDeliverable['events']);
                }
            }
        }

        return $response;
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $options)
    {
        if (isset($options['order'])) {
            $this->defaultOrder = $options['order'];
        }
    }
}