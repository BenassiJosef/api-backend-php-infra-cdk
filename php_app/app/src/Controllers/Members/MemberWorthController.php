<?php
/**
 * Created by jamieaitken on 20/06/2018 at 11:38
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Members;

use App\Models\Integrations\ChargeBee\Invoice;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Models\OauthUser;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class MemberWorthController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function calculateWorthRoute(Request $request, Response $response)
    {
        $send = $this->calculateWorth($request->getAttribute('accessUser'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function topEarnersRoute(Request $request, Response $response)
    {
        $send = $this->topEarners();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function calculateWorth(array $user)
    {
        $users = [$user['uid']];
        if ($user['role'] === 1) {
            $getAdmins       = $this->em->createQueryBuilder()
                ->select('u.uid')
                ->from(OauthUser::class, 'u')
                ->where('u.reseller = :uid')
                ->setParameter('uid', $user['uid'])
                ->getQuery()
                ->getArrayResult();
            $formattedAdmins = [];
            foreach ($getAdmins as $key => $admin) {
                $formattedAdmins[] = $admin['uid'];
            }

            $users = $formattedAdmins;
        }

        $subscriptions = $this->em->createQueryBuilder()
            ->select('u.plan_id, u.plan_unit_price, u.mrr, u.plan_quantity, u.customer_id')
            ->from(Subscriptions::class, 'u')
            ->leftJoin(SubscriptionsAddon::class, 'sa', 'WITH', 'u.subscription_id = sa.subscription_id')
            ->where('u.customer_id IN (:user)')
            ->andWhere('u.status = :active')
            ->setParameter('user', $users)
            ->setParameter('active', 'active')
            ->getQuery()
            ->getArrayResult();

        $invoices = $this->em->createQueryBuilder()
            ->select('u.customer_id, u.total')
            ->from(Invoice::class, 'u')
            ->leftJoin(OauthUser::class, 'a', 'WITH', 'u.customer_id = a.uid')
            ->where('u.customer_id IN (:user)')
            ->andWhere('u.status = :paid')
            ->setParameter('user', $users)
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getArrayResult();

        $results = [
            'currentWorth'   => 0,
            'lifetimeWorth'  => 0,
            'locations'      => [
                'worth'                 => 0,
                'numberOfSubscriptions' => 0,
                'plans'                 => []
            ],
            'legacy'         => [
                'worth'                 => 0,
                'numberOfSubscriptions' => 0,
                'locations'             => [
                    'worth'                 => 0,
                    'numberOfSubscriptions' => 0
                ],
                'marketing'             => [
                    'numberOfSubscriptions' => 0,
                    'worth'                 => 0
                ]
            ],
            'marketing'      => [
                'worth'                 => 0,
                'numberOfSubscriptions' => 0,
                'plans'                 => []
            ],
            'infrastructure' => [
                'worth'                 => 0,
                'numberOfSubscriptions' => 0,
                'plans'                 => []
            ],
            'bespoke'        => [
                'worth'                 => 0,
                'numberOfSubscriptions' => 0,
                'plans'                 => []
            ]
        ];

        foreach (Subscriptions::$currentPlanListChargeBee as $key => $plan) {

            if (strpos($plan, '_an') !== false) {
                continue;
            }

            $results['locations']['plans'][$plan] = [
                'percentageWorth' => 0,
                'worth'           => 0,
                'subscriptions'   => []
            ];
        }

        foreach (Subscriptions::$legacyPlanList as $key => $plan) {
            $results['legacy']['locations']['plans'][$plan] = [
                'percentageWorth' => 0,
                'worth'           => 0,
                'subscriptions'   => []
            ];
        }

        foreach (Subscriptions::$legacyMarketingPlanList as $key => $plan) {
            $results['legacy']['marketing']['plans'][$plan] = [
                'percentageWorth' => 0,
                'worth'           => 0,
                'subscriptions'   => []
            ];
        }

        foreach (Subscriptions::$infrastructurePlanList as $key => $plan) {
            $results['infrastructure']['plans'][$plan] = [
                'percentageWorth' => 0,
                'worth'           => 0,
                'subscriptions'   => []
            ];
        }

        foreach (Subscriptions::$bespokePlanList as $key => $plan) {
            $results['bespoke']['plans'][$plan] = [
                'percentageWorth' => 0,
                'worth'           => 0,
                'subscriptions'   => []
            ];
        }

        foreach ($subscriptions as $key => $subscription) {

            if (strpos($subscription['plan_id'], '_an') !== false) {
                $subscription['mrr']     = $subscription['plan_unit_price'] / 12;
                $subscription['plan_id'] = str_replace('_an', '', $subscription['plan_id']);
            }

            if ($subscription['plan_id'] === 'no-plan') {
                continue;
            }

            if (in_array($subscription['plan_id'], Subscriptions::$currentPlanListChargeBee)) {
                $results['locations']['numberOfSubscriptions'] += 1;
                if ($subscription['mrr'] !== 0) {
                    $results['currentWorth']                                          += $subscription['mrr'];
                    $results['locations']['worth']                                    += $subscription['mrr'];
                    $results['locations']['plans'][$subscription['plan_id']]['worth'] += $subscription['mrr'];

                } else {
                    $results['currentWorth']                                          += $subscription['plan_unit_price'];
                    $results['locations']['worth']                                    += $subscription['plan_unit_price'];
                    $results['locations']['plans'][$subscription['plan_id']]['worth'] += $subscription['plan_unit_price'];
                }
                $results['locations']['plans'][$subscription['plan_id']]['subscriptions'][] = $subscription;
            } elseif (in_array($subscription['plan_id'], Subscriptions::$legacyPlanList)) {
                $results['legacy']['locations']['numberOfSubscriptions'] += 1;
                if ($subscription['mrr'] !== 0) {
                    $results['currentWorth']                                                    += $subscription['mrr'];
                    $results['legacy']['locations']['worth']                                    += $subscription['mrr'];
                    $results['legacy']['locations']['plans'][$subscription['plan_id']]['worth'] += $subscription['mrr'];

                } else {
                    $results['currentWorth']                                                    += $subscription['plan_unit_price'];
                    $results['legacy']['locations']['worth']                                    += $subscription['plan_unit_price'];
                    $results['legacy']['locations']['plans'][$subscription['plan_id']]['worth'] += $subscription['plan_unit_price'];
                }
                $results['legacy']['locations']['plans'][$subscription['plan_id']]['subscriptions'][] = $subscription;
            } elseif (in_array($subscription['plan_id'], Subscriptions::$legacyMarketingPlanList)) {
                $results['currentWorth']                                                              += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
                $results['legacy']['marketing']['numberOfSubscriptions']                              += 1;
                $results['legacy']['marketing']['worth']                                              += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
                $results['legacy']['marketing']['plans'][$subscription['plan_id']]['subscriptions'][] = $subscription;
                $results['legacy']['marketing']['plans'][$subscription['plan_id']]['worth']           += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
            } elseif (in_array($subscription['plan_id'], Subscriptions::$infrastructurePlanList)) {
                $results['currentWorth']                                                         += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
                $results['infrastructure']['numberOfSubscriptions']                              += 1;
                $results['infrastructure']['worth']                                              += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
                $results['infrastructure']['plans'][$subscription['plan_id']]['subscriptions'][] = $subscription;
                $results['infrastructure']['plans'][$subscription['plan_id']]['worth']           += $subscription['plan_unit_price'] * $subscription['plan_quantity'];
            } elseif (in_array($subscription['plan_id'], Subscriptions::$bespokePlanList)) {
                $results['currentWorth']                                                  += $subscription['mrr'];
                $results['bespoke']['numberOfSubscriptions']                              += 1;
                $results['bespoke']['worth']                                              += $subscription['mrr'];
                $results['bespoke']['plans'][$subscription['plan_id']]['subscriptions'][] = $subscription;
                $results['bespoke']['plans'][$subscription['plan_id']]['worth']           += $subscription['mrr'];
            }
        }

        if ($results['locations']['worth'] > 0) {
            $results['locations']['percentageWorth'] = round($results['locations']['worth'] / $results['currentWorth'],
                    2) * 100;

            foreach ($results['locations']['plans'] as $key => $plan) {
                $results['locations']['plans'][$key]['percentageWorth'] = round($results['locations']['plans'][$key]['worth'] / $results['currentWorth'],
                        2) * 100;
            }
        }

        if ($results['legacy']['locations']['worth'] > 0) {
            $results['legacy']['locations']['percentageWorth'] = round($results['legacy']['locations']['worth'] / $results['currentWorth'],
                    2) * 100;

            foreach ($results['legacy']['locations']['plans'] as $key => $plan) {
                $results['legacy']['locations']['plans'][$key]['percentageWorth'] = round($results['legacy']['locations']['plans'][$key]['worth'] / $results['currentWorth'],
                        2) * 100;
            }
        }

        if ($results['legacy']['marketing']['worth'] > 0) {
            $results['legacy']['marketing']['percentageWorth'] = round($results['legacy']['marketing']['worth'] / $results['currentWorth'],
                    2) * 100;

            foreach ($results['legacy']['marketing']['plans'] as $key => $plan) {
                $results['legacy']['marketing']['plans'][$key]['percentageWorth'] = round($results['legacy']['marketing']['plans'][$key]['worth'] / $results['currentWorth'],
                        2) * 100;
            }
        }

        if ($results['infrastructure']['worth'] > 0) {
            $results['infrastructure']['percentageWorth'] = round($results['infrastructure']['worth'] / $results['currentWorth'],
                    2) * 100;

            foreach ($results['infrastructure']['plans'] as $key => $plan) {
                $results['infrastructure']['plans'][$key]['percentageWorth'] = round($results['infrastructure']['plans'][$key]['worth'] / $results['currentWorth'],
                        2) * 100;
            }
        }

        if ($results['bespoke']['worth'] > 0) {
            $results['bespoke']['percentageWorth'] = round($results['bespoke']['worth'] / $results['currentWorth'],
                    2) * 100;

            foreach ($results['bespoke']['plans'] as $key => $plan) {
                $results['bespoke']['plans'][$key]['percentageWorth'] = round($results['bespoke']['plans'][$key]['worth'] / $results['currentWorth'],
                        2) * 100;
            }
        }

        foreach ($invoices as $key => $invoice) {
            $results['lifetimeWorth'] += $invoice['total'];
        }

        return Http::status(200, $results);
    }

    public function topEarners()
    {
        $invoices = $this->em->createQueryBuilder()
            ->select('u.customer_id, SUM(u.total) as mrr, a.company, a.email, a.first, a.last')
            ->from(Invoice::class, 'u')
            ->leftJoin(OauthUser::class, 'a', 'WITH', 'u.customer_id = a.uid')
            ->where('u.status = :paid')
            ->setParameter('paid', 'paid')
            ->orderBy('mrr', 'DESC')
            ->groupBy('u.customer_id')
            ->setMaxResults(50)
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $invoices);
    }
}