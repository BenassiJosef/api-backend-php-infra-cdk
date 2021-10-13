<?php
/**
 * Created by jamieaitken on 02/05/2018 at 10:24
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Info;

use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\Locations\LocationSettings;
use App\Models\NetworkAccess;
use App\Models\OauthUser;
use App\Models\RadiusVendor;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationsInfoController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $serial)
    {

        $responseStructure = [
            'vendor'              => '',
            'subscription'        => false,
            'model'               => '',
            'ip'                  => '',
            'alias'               => '',
            'subscriptionDetails' => [
                'isAnnual'        => false,
                'plan'            => '',
                'subscription_id' => '',
                'addOnFlags'      => [
                    'hasMarketingAutomation' => false,
                    'hasContentFiltering'    => false,
                    'hasCustomIntegration'   => false,
                    'hasReviews'             => false,
                    'hasStories'             => false
                ],
                'addOns'          => []
            ],
            'customerDetails'     => [
                'uid'             => '',
                'company'         => '',
                'resellerUid'     => '',
                'resellerCompany' => ''
            ]
        ];

        $openmesh = 'openmesh';

        $getInform = $this->em->createQueryBuilder()
            ->select('u.vendor, u.id, u.ip')
            ->from(Inform::class, 'u')
            ->where('u.serial = :ser')
            ->andWhere('u.vendor != :ven')
            ->setParameter('ser', $serial)
            ->setParameter('ven', $openmesh)
            ->getQuery()
            ->getArrayResult();

        if (!empty($getInform)) {
            $responseStructure['vendor'] = strtolower($getInform[0]['vendor']);
            $responseStructure['ip']     = $getInform[0]['ip'];

            if ($responseStructure['vendor'] === 'mikrotik') {
                $getModel = $this->em->createQueryBuilder()
                    ->select('u.model')
                    ->from(MikrotikInform::class, 'u')
                    ->where('u.informId = :inform')
                    ->setParameter('inform', $getInform[0]['id'])
                    ->getQuery()
                    ->getArrayResult();

                $responseStructure['model'] = $getModel[0]['model'];
            }

        } else {
            $getRadius = $this->em->createQueryBuilder()
                ->select('u.vendor')
                ->from(RadiusVendor::class, 'u')
                ->where('u.serial = :ser')
                ->setParameter('ser', $serial)
                ->getQuery()
                ->getArrayResult();

            if (!empty($getRadius)) {
                $responseStructure['vendor'] = strtolower($getRadius[0]['vendor']);
            }
        }

        $getAlias = $this->em->createQueryBuilder()
                        ->select('u.alias')
                        ->from(LocationSettings::class, 'u')
                        ->where('u.serial = :serial')
                        ->setParameter('serial', $serial)
                        ->getQuery()
                        ->getArrayResult()[0];

        if (!is_null($getAlias)) {
            $responseStructure['alias'] = $getAlias['alias'];
        }

        $hasSubscription = $this->em->createQueryBuilder()
            ->select('u.admin, u.reseller')
            ->from(NetworkAccess::class, 'u')
            ->where('u.serial = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        $oauthDetails = [];

        $oauthDetails[] = $hasSubscription[0]['admin'];
        $oauthDetails[] = $hasSubscription[0]['reseller'];

        $companyDetails = $this->em->createQueryBuilder()
            ->select('u.uid, u.company')
            ->from(OauthUser::class, 'u')
            ->where('u.uid IN (:details)')
            ->setParameter('details', $oauthDetails)
            ->getQuery()
            ->getArrayResult();

        foreach ($companyDetails as $detail) {
            if ($detail['uid'] === $hasSubscription[0]['admin']) {
                $responseStructure['customerDetails']['uid']     = $detail['uid'];
                $responseStructure['customerDetails']['company'] = $detail['company'];
            } else {
                $responseStructure['customerDetails']['resellerUid']     = $detail['uid'];
                $responseStructure['customerDetails']['resellerCompany'] = $detail['company'];
            }
        }

        if (!is_null($hasSubscription[0]['admin'])) {
            $responseStructure['subscription'] = true;

            $getPlan = $this->em->createQueryBuilder()
                ->select('u.plan_id, u.subscription_id, sa.add_on_id, u.id, u.subscription_id')
                ->from(Subscriptions::class, 'u')
                ->leftJoin(SubscriptionsAddon::class, 'sa', 'WITH', 'u.subscription_id = sa.subscription_id')
                ->where('u.serial = :ser')
                ->andWhere('u.plan_id IN (:planArray)')
                ->andWhere('u.status IN (:statusArray)')
                ->setParameter('ser', $serial)
                ->setParameter('planArray', [
                    'starter',
                    'starter_an',
                    'all-in',
                    'all-in_an'
                ])
                ->setParameter('statusArray', ['active', 'in_trial'])
                ->getQuery()
                ->getArrayResult();

            if (!empty($getPlan)) {
                $responseStructure['subscriptionDetails']['plan']            = $getPlan[0]['plan_id'];
                $responseStructure['subscriptionDetails']['subscription_id'] = $getPlan[0]['subscription_id'];


                if (strpos($getPlan[0]['plan_id'], '_an') !== false) {
                    $responseStructure['subscriptionDetails']['isAnnual'] = true;
                    $responseStructure['subscriptionDetails']['plan']     = str_replace('_an', '',
                        $responseStructure['subscriptionDetails']['plan']);
                }

                if ($responseStructure['subscriptionDetails']['plan'] === 'all-in') {
                    $responseStructure['subscriptionDetails']['addOnFlags']['hasContentFiltering']    = true;
                    $responseStructure['subscriptionDetails']['addOnFlags']['hasMarketingAutomation'] = true;
                    $responseStructure['subscriptionDetails']['addOnFlags']['hasCustomIntegration']   = true;
                    $responseStructure['subscriptionDetails']['addOnFlags']['hasReviews']             = true;
                    $responseStructure['subscriptionDetails']['addOnFlags']['hasStories']             = true;
                    $responseStructure['subscriptionDetails']['addOns'][]['id']                       = 'content-filter';
                    $responseStructure['subscriptionDetails']['addOns'][]['id']                       = 'marketing-automation';
                    $responseStructure['subscriptionDetails']['addOns'][]['id']                       = 'custom-integration';
                    $responseStructure['subscriptionDetails']['addOns'][]['id']                       = 'reviews';
                    $responseStructure['subscriptionDetails']['addOns'][]['id']                       = 'stories';


                    return Http::status(200, $responseStructure);

                }


                foreach ($getPlan as $key => $addOn) {

                    if (is_null($addOn['add_on_id'])) {
                        continue;
                    }

                    if (strpos($addOn['add_on_id'], 'content-filter') !== false) {
                        $responseStructure['subscriptionDetails']['addOnFlags']['hasContentFiltering'] = true;
                        array_push($responseStructure['subscriptionDetails']['addOns'], ['id' => $addOn['add_on_id']]);
                    } elseif (strpos($addOn['add_on_id'], 'marketing-automation') !== false) {
                        $responseStructure['subscriptionDetails']['addOnFlags']['hasMarketingAutomation'] = true;
                        array_push($responseStructure['subscriptionDetails']['addOns'], ['id' => $addOn['add_on_id']]);
                    } elseif (strpos($addOn['add_on_id'], 'custom-integration') !== false) {
                        $responseStructure['subscriptionDetails']['addOnFlags']['hasCustomIntegration'] = true;
                        array_push($responseStructure['subscriptionDetails']['addOns'], ['id' => $addOn['add_on_id']]);
                    } elseif (strpos($addOn['add_on_id'], 'reviews') !== false) {
                        $responseStructure['subscriptionDetails']['addOnFlags']['hasReviews'] = true;
                        array_push($responseStructure['subscriptionDetails']['addOns'], ['id' => $addOn['add_on_id']]);
                    } elseif (strpos($addOn['add_on_id'], 'stories') !== false) {
                        $responseStructure['subscriptionDetails']['addOnFlags']['hasStories'] = true;
                        array_push($responseStructure['subscriptionDetails']['addOns'], ['id' => $addOn['add_on_id']]);
                    }
                }

            }

        }

        return Http::status(200, $responseStructure);
    }
}