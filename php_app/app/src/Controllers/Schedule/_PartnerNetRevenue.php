<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/10/2017
 * Time: 16:20
 */

namespace App\Controllers\Schedule;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Members\MemberWorthController;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\OauthUser;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _PartnerNetRevenue
{
    protected $em;
    protected $mail;

    private $financeEmails = [
        'helen@stampede.ai',
        'patrick@stampede.ai'
    ];
    private $financeNames = [
        'Helen Clover',
        'Patrick Clover'
    ];

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }

    public function runRoute(Request $request, Response $response)
    {
        $send = $this->run();

        $this->em->clear();

        return $response->withJson($send, 200);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get();

        $this->em->clear();

        return $response->withJson($send, 200);
    }

    public function get()
    {

        $plansToNegate = array_merge(
            Subscriptions::$bespokePlanList,
            Subscriptions::$legacyMarketingPlanList,
            Subscriptions::$infrastructurePlanList,
            [
                'no-plan',
                'demo'
            ]
        );

        $getPartners = $this->em->createQueryBuilder()
            ->select('u.uid, u.first, u.last, u.email, u.company')
            ->from(OauthUser::class, 'u')
            ->where('u.role = :role')
            ->andWhere('u.uid != :bxReseller') // TODO OrgId replace
            ->setParameter('role', 1)
            ->setParameter('bxReseller', 'fc34eaf5-4a01-4c29-be45-0d112847a21c')
            ->getQuery()
            ->getArrayResult();

        $response = [];

        foreach ($getPartners as $key => $partner) {
            $response[] = $partner['uid'];
        }

        $getAdmins = $this->em->createQueryBuilder()
            ->select('u.uid, u.reseller')
            ->from(OauthUser::class, 'u')
            ->where('u.reseller IN (:uid)')
            ->andWhere('u.role = :two')
            ->setParameter('uid', $response)
            ->setParameter('two', 2)
            ->getQuery()
            ->getArrayResult();

        $admins = [];

        foreach ($getAdmins as $ke => $admin) {
            $admins[] = $admin['uid'];
            foreach ($getPartners as $key => $partner) {
                if ($admin['reseller'] === $partner['uid']) {
                    $getPartners[$key]['admins'][] = $admin['uid'];
                }
            }
        }

        $getSubsForResellers = $this->em->createQueryBuilder()
            ->select('b.reseller, u.subscription_id, u.plan_id, u.serial, u.mrr, b.first, b.last, b.company, b.email, u.plan_unit_price, u.plan_quantity')
            ->from(Subscriptions::class, 'u')
            ->join(OauthUser::class, 'b', 'WITH', 'u.customer_id = b.uid')
            ->where('u.customer_id IN (:customer)')
            ->andWhere('u.plan_id NOT IN (:plansToNegate)')
            ->andWhere('u.status = :status')
            ->setParameter('customer', $admins)
            ->setParameter('plansToNegate', $plansToNegate)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getArrayResult();

        foreach ($getPartners as $key => $partner) {
            if (!isset($partner['admins'])) {
                unset($getPartners[$key]);
                continue;
            }
            $getPartners[$key]['net_revenue']   = 0;
            $getPartners[$key]['subscriptions'] = [];

            foreach ($getSubsForResellers as $ke => $subscription) {

                if ($subscription['reseller'] !== $partner['uid']) {
                    continue;
                }

                if (strpos($subscription['plan_id'], '_an') !== false) {
                    $subscription['mrr']     = $subscription['plan_unit_price'] / 12;
                    $subscription['plan_id'] = str_replace('_an', '', $subscription['plan_id']);
                }


                if (in_array(
                    $subscription['plan_id'],
                    Subscriptions::$legacyPlanList
                ) || in_array(
                    $subscription['plan_id'],
                    Subscriptions::$currentPlanListChargeBee
                )) {
                    if ($subscription['mrr'] !== 0) {
                        $getPartners[$key]['net_revenue'] += $subscription['mrr'];
                        $subscription['mrr']              = $subscription['mrr'];
                    } else {
                        $subscription['mrr']              = $subscription['plan_unit_price'];
                        $getPartners[$key]['net_revenue'] += $subscription['plan_unit_price'];
                    }
                }

                if ($subscription['mrr'] !== 0) {
                    $getPartners[$key]['subscriptions'][] = $subscription;
                }
            }
        }

        foreach ($getPartners as $key => $partner) {
            if ($partner['net_revenue'] === 0) {
                unset($getPartners[$key]);
            }
        }

        $getPartners = array_values($getPartners);

        return Http::status(200, $getPartners);
    }

    public function run()
    {

        $plansToNegate = array_merge(
            Subscriptions::$bespokePlanList,
            Subscriptions::$legacyMarketingPlanList,
            Subscriptions::$infrastructurePlanList,
            [
                'no-plan',
                'demo'
            ]
        );

        $getPartners = $this->em->createQueryBuilder()
            ->select('u.uid, u.first, u.last, u.email, u.company')
            ->from(OauthUser::class, 'u')
            ->where('u.role = :role')
            ->andWhere('u.uid != :bxReseller')
            ->setParameter('role', 1)
            ->setParameter('bxReseller', 'fc34eaf5-4a01-4c29-be45-0d112847a21c')
            ->getQuery()
            ->getArrayResult();

        $response = [];

        foreach ($getPartners as $key => $partner) {
            $response[] = $partner['uid'];
        }

        $getAdmins = $this->em->createQueryBuilder()
            ->select('u.uid, u.reseller')
            ->from(OauthUser::class, 'u')
            ->where('u.reseller IN (:uid)')
            ->andWhere('u.role = :two')
            ->setParameter('uid', $response)
            ->setParameter('two', 2)
            ->getQuery()
            ->getArrayResult();

        $admins = [];

        foreach ($getAdmins as $ke => $admin) {
            $admins[] = $admin['uid'];
            foreach ($getPartners as $key => $partner) {
                if ($admin['reseller'] === $partner['uid']) {
                    $getPartners[$key]['admins'][] = $admin['uid'];
                }
            }
        }

        foreach ($getPartners as $key => $partner) {
            if (!isset($partner['admins'])) {
                unset($getPartners[$key]);
                continue;
            }

            $getPartners[$key]['net_revenue']   = 0;
            $getPartners[$key]['subscriptions'] = [];

            $getSubsForReseller = $this->em->createQueryBuilder()
                ->select('u.subscription_id, u.plan_id, u.serial, u.mrr, b.first, b.last, b.company, u.plan_unit_price, u.plan_quantity')
                ->from(Subscriptions::class, 'u')
                ->join(OauthUser::class, 'b', 'WITH', 'u.customer_id = b.uid')
                ->where('u.customer_id IN (:customer)')
                ->andWhere('u.plan_id NOT IN (:plansToNegate)')
                ->andWhere('u.status = :status')
                ->setParameter('customer', $partner['admins'])
                ->setParameter('plansToNegate', $plansToNegate)
                ->setParameter('status', 'active')
                ->getQuery()
                ->getArrayResult();

            foreach ($getSubsForReseller as $ke => $subscription) {

                if (strpos($subscription['plan_id'], '_an') !== false) {
                    $subscription['mrr']     = $subscription['plan_unit_price'] / 12;
                    $subscription['plan_id'] = str_replace('_an', '', $subscription['plan_id']);
                }


                if (in_array(
                    $subscription['plan_id'],
                    Subscriptions::$legacyPlanList
                ) || in_array(
                    $subscription['plan_id'],
                    Subscriptions::$currentPlanListChargeBee
                )) {
                    if ($subscription['mrr'] !== 0) {
                        $getPartners[$key]['net_revenue'] += $subscription['mrr'] / 100;
                        $subscription['mrr']              = $subscription['mrr'] / 100;
                    } else {
                        $subscription['mrr']              = $subscription['plan_unit_price'] / 100;
                        $getPartners[$key]['net_revenue'] += $subscription['plan_unit_price'] / 100;
                    }
                }

                if ($subscription['mrr'] !== 0) {
                    $getPartners[$key]['subscriptions'][] = $subscription;
                }
            }
        }

        foreach ($getPartners as $key => $partner) {
            if ($partner['net_revenue'] === 0) {
                unset($getPartners[$key]);
            }
        }

        $getPartners = array_values($getPartners);

        $newDate       = new \DateTime();
        $formattedDate = $newDate->format('l jS F Y');

        foreach ($this->financeEmails as $key => $person) {
            $this->mail->send(
                [
                    [
                        'to'   => $person,
                        'name' => $this->financeNames[$key]
                    ]
                ],
                [
                    'partners' => $getPartners,
                    'date'     => $formattedDate
                ],
                'PartnerNetRev',
                'Partner Monthly Kickback Report: ' . $newDate->format('F Y')
            );
        }

        return Http::status(200, $getPartners);
    }
}
