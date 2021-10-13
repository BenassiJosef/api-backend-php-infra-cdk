<?php

namespace App\Controllers\Migrations;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 12/01/2017
 * Time: 15:21
 */

use App\Controllers\Integrations\S3\S3;
use App\Models\CustomerCoupon;
use App\Models\EmailAlerts;
use App\Models\Integrations\ChargeBee\CouponApplied;
use App\Models\Integrations\ChargeBee\Invoice;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Models\Integrations\PayPal\PayPalAccount;
use App\Models\Integrations\PayPal\PayPalAccountAccess;
use App\Models\Integrations\UniFi\UnifiController;
use App\Models\Integrations\UniFi\UnifiControllerList;
use App\Models\Integrations\UniFi\UniFiLegacy;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\Invoices;
use App\Models\InvoicesLines;
use App\Models\Locations\Bandwidth\LocationBandwidth;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Schedule\LocationSchedule;
use App\Models\Locations\Schedule\LocationScheduleDay;
use App\Models\Locations\Schedule\LocationScheduleTime;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\Timeout\LocationTimeout;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Models\LocationTypes;
use App\Models\MarketingCampaigns;
use App\Models\MarketingMessages;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\NetworkSettings;
use App\Models\Notifications\NotificationType;
use App\Models\OauthUser;
use App\Models\PartnerQuoteItems;
use App\Models\PartnerQuotes;
use App\Models\RadiusVendor;
use App\Models\StripeSubscriptionPlans;
use App\Models\StripeSubscriptions;
use App\Models\Unifi;
use App\Models\UserData;
use App\Models\UserPayments;
use App\Package\Organisations\OrganisationIdProvider;
use App\Templates\TwigEnvironmentLoader;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Firebase\Database;
use Slim\Http\Response;
use Slim\Http\Request;

class _MigrationsController
{
    protected $em;
    protected $s3;
    protected $view;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->s3   = new S3('', '');
        $createView = new TwigEnvironmentLoader();
        $this->view = $createView->getView();
        $this->organisationIdProvider = new OrganisationIdProvider($this->em);
    }

    public function testRefRoute(Request $request, Response $response)
    {
        return $response->withJson([]);
    }

    public function migrateRoute(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        $send   = $this->migrateQuotePlans();

        return $response->write(
            json_encode($send)
        );
    }

    public function migrateEverythingToHtmlTemplateTypeRoute(Request $request, Response $response)
    {
        $send = $this->migrateEverythingToHtmlTemplateType();

        return $response->withJson($send);
    }

    public function backDateReviewsRoute(Request $request, Response $response)
    {
        $send = $this->backdateReviews();

        return $response->withJson($send);
    }

    public function createVirtualSerialsRoute(Request $request, Response $response)
    {
        $send = $this->createVirtualSerials();

        return $response->withJson($send);
    }

    public function createVirtualSerials()
    {
        $fetchMikrotiks = $this->em->createQueryBuilder()
            ->select('u')
            ->from(Inform::class, 'u')
            ->where('u.vendor = :vendor')
            ->setParameter('vendor', 'MIKROTIK')
            ->getQuery()
            ->getArrayResult();

        foreach ($fetchMikrotiks as $key => $mikrotik) {

            $newSymlink                = new MikrotikSymlinkSerial($mikrotik['serial']);
            $newSymlink->virtualSerial = $mikrotik['serial'];

            $this->em->persist($newSymlink);
        }

        $this->em->flush();
    }

    public function backDateReviews()
    {
        $users = $this->em->createQueryBuilder()
            ->select('u.uid, u.email')
            ->from(OauthUser::class, 'u')
            ->where('u.role = :two')
            ->setParameter('two', 2)
            ->getQuery()
            ->getArrayResult();

        foreach ($users as $key => $user) {
            $doesExist = $this->em->getRepository(NotificationType::class)->findOneBy([
                'uid'              => $user['uid'],
                'notificationKind' => 'review_received',
                'type'             => 'email'
            ]);

            if (is_object($doesExist)) {

                $doesExist->additionalInfo = $user['email'];
                continue;
            }

            $newEmailNotification                 = new NotificationType($user['uid'], 'email', 'review_received');
            $newEmailNotification->additionalInfo = $user['email'];

            $this->em->persist($newEmailNotification);

            $newConnectNotification = new NotificationType($user['uid'], 'connect', 'review_received');

            $this->em->persist($newConnectNotification);
        }

        $this->em->flush();
    }

    public function enforceEmailReportsRoute(Request $request, Response $response)
    {
        $params = $request->getQueryParams();

        $offset = 0;

        if (array_key_exists('offset', $params)) {
            $offset = $params['offset'];
        }

        $send = $this->enforceEmailReports($offset);

        return $response->withJson($send, $send['status']);
    }

    public function migrateMembersRoute(Request $request, Response $response)
    {
        $send = $this->migrateMembers();

        return $response->withStatus($send['status'])->write(
            json_encode($send)
        );
    }

    public function migrateBrandingRoute(Request $request, Response $response)
    {
        $send = $this->migrateBranding($request->getAttribute('serial'));

        return $response->withStatus($send['status'])->write(
            json_encode($send)
        );
    }

    public function createTypesRoute(Request $request, Response $response)
    {
        $send = $this->locationTypes();

        return $response->write(
            json_encode($send)
        );
    }

    public function migratePaypalRoute(Request $request, Response $response)
    {
        $offset = 0;

        $params = $request->getQueryParams();

        if (array_key_exists('offset', $params)) {
            $offset = $params['offset'];
        }

        $send = $this->migratePaypal($offset);

        return $response->withJson($send);
    }

    public function migrateMarketingRoute(Request $request, Response $response)
    {
        $adminUid = $request->getAttribute('id');
        $send     = $this->migrateMarketing($adminUid);

        return $response->withStatus($send['status'])->write(
            json_encode($send)
        );
    }

    public function migrateImages(Request $request, Response $response)
    {
        $send = $this->migrateImagesToS3($request->getAttribute('offset'));

        return $response->withJson($send, $send['status']);
    }

    public function migrateInvoicesRoute(Request $request, Response $response)
    {
        $send = $this->migrateInvoices($request->getQueryParams()['offset']);

        return $response->withJson($send, $send['status']);
    }

    public function vacantSitesRoute(Request $request, Response $response)
    {

        $send = $this->vacantSites($request->getQueryParams()['offset']);

        $offset = 0;

        $params = $request->getQueryParams();

        if (array_key_exists('offset', $params)) {
            $offset = $params['offset'];
        }

        $send = $this->vacantSites($offset);

        return $response->withJson($send, $send['status']);
    }


    public function migrateUniFiControllersRoute(Request $request, Response $response)
    {

        $offset = 0;

        $queryParams = $request->getQueryParams();

        if (array_key_exists('offset', $queryParams)) {
            $offset = $queryParams['offset'];
        }

        $send = $this->migrateUniFiControllers($offset);

        return $response->withJson($send);
    }

    public function enforceAdminsToHaveDefaultNotificationsRoute(Request $request, Response $response)
    {
        $offset = 0;

        $params = $request->getQueryParams();

        if (array_key_exists('offset', $params)) {
            $offset = $params['offset'];
        }

        $send = $this->enforceAdminsToHaveDefaultNotifications($offset);

        return $response->withJson($send, $send['status']);
    }

    public function normalizeNetworkSettingsRoute(Request $request, Response $response)
    {
        $offset = 0;

        $params = $request->getQueryParams();

        if (array_key_exists('offset', $params)) {
            $offset = $params['offset'];
        }

        $send = $this->normalizeNetworkSettings($offset);

        return $response->withJson($send, $send['status']);
    }

    public function vacantSites($offset)
    {
        $getAll = $this->em->createQueryBuilder()
            ->select('ns.serial, COUNT(u.id) cud')
            ->from(NetworkSettings::class, 'ns')
            ->leftJoin(Subscriptions::class, 's', 'WITH', 'ns.serial = s.serial')
            ->leftJoin(UserData::class, 'u', 'WITH', 's.serial = u.serial')
            ->where('s.id IS NULL')
            ->orderBy('ns.id', 'ASC')
            ->groupBy('ns.serial')
            ->setFirstResult($offset)
            ->setMaxResults(100);

        $results = new Paginator($getAll);
        $results->setUseOutputWalkers(false);

        $getAll = $results->getIterator()->getArrayCopy();

        if (empty($getAll)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($getAll) !== $return['total']) {
            $return['has_more'] = true;
        }

        $serials = [];

        foreach ($getAll as $key => $value) {
            $intValue = (int)$value['cud'];
            if ($intValue <= 0) {
                $serials[] = $value['serial'];
            }
        }

        $this->em->createQueryBuilder()
            ->delete(UserData::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(UserPayments::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $getAll)
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        $selectMemberKeys = $this->em->createQueryBuilder()
            ->select('u.memberKey')
            ->from(NetworkAccess::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->getArrayResult();

        $this->em->createQueryBuilder()
            ->delete(NetworkAccessMembers::class, 'u')
            ->where('u.memberKey IN (:s)')
            ->setParameter('s', $selectMemberKeys)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(NetworkSettings::class, 'n')
            ->where('n.serial IN (:s)')
            ->setParameter('s', $getAll)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(UniFiLegacy::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(RadiusVendor::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(EmailAlerts::class, 'u')
            ->where('u.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(NetworkSettings::class, 'n')
            ->where('n.serial IN (:s)')
            ->setParameter('s', $serials)
            ->getQuery()
            ->execute();

        foreach ($getAll as $key => $value) {
            $this->firebase->getReference('dashboard/networks/' . $value['serial'])->remove();
        }

        return Http::status(200, $return);
    }

    public function migrateUniFiControllers($offset)
    {
        $getLegacy = $this->em->createQueryBuilder()
            ->select(
                'u.unifiId, 
            u.serial, 
            u.hostname, 
            u.username, 
            u.password, 
            u.timeout, 
            u.status, 
            u.timeout,
            u.status, 
            u.deleted, 
            u.lastRequest, 
            u.version'
            )
            ->from(UniFiLegacy::class, 'u')
            ->where('u.hostname IS NOT NULL')
            ->andWhere('u.username IS NOT NULL')
            ->andWhere('u.password IS NOT NULL')
            ->orderBy('u.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(100);

        $results = new Paginator($getLegacy);
        $results->setUseOutputWalkers(false);

        $getLegacy = $results->getIterator()->getArrayCopy();

        if (empty($getLegacy)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($getLegacy) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($results as $key => $result) {

            $doesControllerExist = $this->em->getRepository(UnifiController::class)->findOneBy([
                'hostname' => $result['hostname'],
                'username' => $result['username'],
                'password' => $result['password']
            ]);

            $getAdminOfLocation = $this->em->createQueryBuilder()
                ->select('u.admin')
                ->from(NetworkAccess::class, 'u')
                ->where('u.serial = :s')
                ->setParameter('s', $result['serial'])
                ->getQuery()
                ->getArrayResult();

            if (empty($getAdminOfLocation)) {
                continue;
            }

            if (is_null($result['version'])) {
                $result['version'] = '';
            }


            if (is_null($doesControllerExist)) {
                $doesControllerExist = new UnifiController(
                    $result['hostname'],
                    $result['username'],
                    $result['password'],
                    $result['version']
                );
                $this->em->persist($doesControllerExist);
                $this->em->flush();
            }

            $doesLocationExist = $this->em->getRepository(UnifiLocation::class)->findOneBy([
                'serial' => $result['serial']
            ]);

            if (is_null($doesLocationExist)) {
                $newUniFiLocation                    = new UnifiLocation($result['serial']);
                $newUniFiLocation->unifiControllerId = $doesControllerExist->id;
                $newUniFiLocation->unifiId           = $result['unifiId'];
                $newUniFiLocation->timeout           = $result['timeout'];
                $newUniFiLocation->status            = $result['status'];
                $this->em->persist($newUniFiLocation);
            } else {
                $doesLocationExist->unifiControllerId = $doesControllerExist->id;
                $doesLocationExist->unifiId           = $result['unifiId'];
                $doesLocationExist->timeout           = $result['timeout'];
                $doesLocationExist->status            = $result['status'];
            }

            $organisation = $this->organisationIdProvider->getIds($getAdminOfLocation[0]['admin']);

            $assignControllerToUser               = new UnifiControllerList($organisation);
            $assignControllerToUser->controllerId = $doesControllerExist->id;

            $this->em->persist($assignControllerToUser);
        }

        $this->em->flush();

        return Http::status(200, $return);
    }

    public function migrateInvoices($offset)
    {

        $plans = [
            'small',
            'medium',
            'large',
            'demo',
            'trial',
            'small_an',
            'medium_an',
            'large_an'
        ];

        $getOldInvoices = $this->em->createQueryBuilder()
            ->select('u')
            ->from(Invoices::class, 'u')
            ->orderBy('u.id', 'asc')
            ->setFirstResult($offset)
            ->setMaxResults(100)
            ->getQuery()
            ->getArrayResult();

        $oldInvoicesKeys = [];

        foreach ($getOldInvoices as $oldInvoice) {
            $beginningTerm = new \DateTime();
            $endTerm       = new \DateTime();
            $beginningTerm->setTimestamp($oldInvoice['period_start']);
            $endTerm->setTimestamp($oldInvoice['period_end']);
            $netTerm                        = date_diff($endTerm, $beginningTerm);
            $newInvoice                     = new Invoice();
            $newInvoice->invoice_id         = 'INVS-' . $oldInvoice['id'];
            $newInvoice->price_type         = 'tax_exclusive';
            $newInvoice->object             = 'invoice';
            $newInvoice->customer_id        = $oldInvoice['customer'];
            $newInvoice->net_term_days      = $netTerm->d;
            $newInvoice->exchange_rate      = 1.0;
            $newInvoice->currency_code      = $oldInvoice['currency'];
            $newInvoice->base_currency_code = $oldInvoice['currency'];
            $newInvoice->amount_due         = $oldInvoice['amount_due'];
            $newInvoice->tax                = $oldInvoice['tax'];
            $newInvoice->next_retry_at      = $oldInvoice['next_payment_attempt'];
            $newInvoice->sub_total          = $oldInvoice['subtotal'];
            $newInvoice->total              = $oldInvoice['total'];
            $newInvoice->date               = $oldInvoice['date'];
            $newInvoice->due_date           = $oldInvoice['date'];
            if ($oldInvoice['paid'] === true) {
                $newInvoice->status      = 'paid';
                $newInvoice->amount_paid = $oldInvoice['total'];
            } else {
                $newInvoice->status = 'not_paid';
            }

            if ($oldInvoice['attempt_count'] > 0) {
                if ($oldInvoice['attempt_count'] === 1) {
                    $status = 'in_progress';
                } elseif ($oldInvoice['attempt_count'] === 2) {
                    $status = 'exhausted';
                } elseif ($oldInvoice['attempt_count'] === 3) {
                    $status = 'stopped';
                }
                $newInvoice->dunning_status = $status;
            }

            $this->em->persist($newInvoice);

            $this->em->flush();
            $getLineItems = $this->em->createQueryBuilder()
                ->select('l')
                ->from(InvoicesLines::class, 'l')
                ->where('l.invoice = :id')
                ->setParameter('id', $oldInvoice['id'])
                ->getQuery()
                ->getArrayResult();
            if (empty($getLineItems)) {
                continue;
            }
            if (array_search($oldInvoice['subscription'], array_column($oldInvoicesKeys, 'sub')) === false) {
                $oldInvoicesKeys[] = [
                    'sub'      => $oldInvoice['subscription'],
                    'customer' => $oldInvoice['customer']
                ];
            }
        }

        foreach ($oldInvoicesKeys as $subscription) {
            $getSubscription = $this->em->createQueryBuilder()
                ->select('s', 'p')
                ->from(StripeSubscriptions::class, 's')
                ->join(StripeSubscriptionPlans::class, 'p', 'WITH', 's.subscriptionId = p.subscriptionId')
                ->where('s.subscriptionId =:sub')
                ->setParameter('sub', $subscription['sub'])
                ->getQuery()
                ->getArrayResult();

            foreach ($getSubscription as $sub) {
                if (array_key_exists('serial', $sub)) {
                    $newSubscription                     = new Subscriptions();
                    $newSubscription->subscription_id    = $subscription['sub'];
                    $newSubscription->customer_id        = $subscription['customer'];
                    $newSubscription->plan_free_quantity = 0;
                    $newSubscription->plan_quantity      = 1;
                    $newSubscription->serial             = $sub['serial'];
                    $newSubscription->current_term_start = $sub['startDate'];
                    $newSubscription->current_term_end   = $sub['endDate'];
                    $newSubscription->start_date         = $sub['createdAt']->getTimestamp();
                    $newSubscription->created_at         = $sub['createdAt']->getTimestamp();
                    $newSubscription->started_at         = $sub['createdAt']->getTimestamp();
                    $newSubscription->activated_at       = $sub['createdAt']->getTimestamp();
                    if ($sub['status'] === 'trialing') {
                        $newSubscription->status = 'in_trial';
                    } elseif ($sub['status'] === 'canceled') {
                        $newSubscription->status = 'cancelled';
                    } else {
                        $newSubscription->status = 'active';
                    }
                    $this->em->persist($newSubscription);
                    $this->em->flush();
                } elseif (array_key_exists('planId', $sub)) {
                    if (!in_array(strtolower($sub['planId']), $plans)) {
                        $newSubscriptionAddon = new SubscriptionsAddon($newSubscription->id, $sub['planId'], 1, 0);
                        $this->em->persist($newSubscriptionAddon);
                    } else {
                        $newSubscription->plan_id = $sub['planId'];
                        $this->em->persist($newSubscription);
                    }
                }
            }
        }


        $this->em->flush();


        return Http::status(200);
    }

    public function enforceEmailReports($offset)
    {
        $getUsers = $this->em->createQueryBuilder()
            ->select('u.uid, u.email')
            ->from(OauthUser::class, 'u')
            ->where('u.role >= :two')
            ->setParameter('two', 2)
            ->orderBy('u.created', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(100);

        $results = new Paginator($getUsers);
        $results->setUseOutputWalkers(false);

        $getUsers = $results->getIterator()->getArrayCopy();

        if (empty($getUsers)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($getUsers) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($getUsers as $user) {

            $doesAlreadyExist = $this->em->getRepository(NotificationType::class)->findOneBy([
                'uid'              => $user['uid'],
                'type'             => 'email',
                'notificationKind' => 'insight_weekly'
            ]);
            if (is_object($doesAlreadyExist)) {
                continue;
            }

            $newConnectNotification               = new NotificationType($user['uid'], 'connect', 'insight_weekly');
            $newEmailNotification                 = new NotificationType($user['uid'], 'email', 'insight_weekly');
            $newEmailNotification->additionalInfo = $user['email'];
            $this->em->persist($newConnectNotification);
            $this->em->persist($newEmailNotification);
        }

        $this->em->flush();


        return Http::status(200, $return);
    }

    public function migrateCoupons()
    {
        $getCoupons = $this->em->createQueryBuilder()
            ->select('u')
            ->from(CustomerCoupon::class, 'u')
            ->getQuery()
            ->getArrayResult();

        foreach ($getCoupons as $coupon) {
            $newCoupon = new CouponApplied();
            if ($coupon['couponId'] === '15%_OFF') {
                $newCoupon->couponId = '15%OFF';
            } elseif ($coupon['couponId'] === '17%_OFF') {
                $newCoupon->couponId = '17%OFF';
            } elseif ($coupon['couponId'] === '20%_OFF') {
                $newCoupon->couponId = '20%OFF';
            } elseif ($coupon['couponId'] === '28%_OFF') {
                $newCoupon->couponId = '28%OFF';
            } elseif ($coupon['couponId'] === '40%_OFF') {
                $newCoupon->couponId = '40%OFF';
            } elseif ($coupon['couponId'] === '43%_OFF') {
                $newCoupon->couponId = '43%OFF';
            } elseif ($coupon['couponId'] === '60%_OFF') {
                $newCoupon->couponId = '60%OFF';
            }
            $newCoupon->uid = $coupon['userId'];
            $this->em->persist($newCoupon);
        }
        $this->em->flush();

        return Http::status(200);
    }

    public function migrateMarketing($id)
    {
        return Http::status(501);
    }

    public function migration()
    {
        /*$qb                      = $this->em->createQueryBuilder();
        $getCurrentAuthenticated = $qb->select('u.devices, u.pricing, u.serial')
            ->from(NetworkSettings::class, 'u')
            ->where('u.type >= 1')
            ->getQuery()
            ->getArrayResult();


        foreach ($getCurrentAuthenticated as $network) {
            if (!is_null($network['pricing'])) {

                $admin = $this->em->createQueryBuilder()->select('p.admin')
                    ->from(NetworkAccess::class, 'p')
                    ->where('p.serial = :serial')
                    ->setParameter('serial', $network['serial'])
                    ->getQuery()
                    ->getArrayResult();

                $plansArr = [];
                foreach ($network['pricing'] as $res) {

                    $newPlan = new LocationPlan(
                        $admin[0]['admin'],
                        $res['name'],
                        $getCurrentAuthenticated[0]['devices'],
                        $res['duration'],
                        $res['price'] * 100
                    );

                    $this->em->persist($newPlan);
                    $this->em->flush();

                    $plansArr[$newPlan->id] = [
                        'name'            => $newPlan->name,
                        'cost'            => $newPlan->cost,
                        'deviceAllowance' => $newPlan->deviceAllowance,
                        'duration'        => $newPlan->duration
                    ];

                    $newPlanSerial = new LocationPlanSerial(
                        $newPlan->id,
                        $network['serial']
                    );


                    $this->em->persist($newPlanSerial);
                    $this->em->flush();
                }

                $this->firebase->getReference('dashboard/networks/' . $network['serial'] . '/settings/payments/plans')->set(
                    $plansArr
                );
            }
        }*/
    }

    public function findMemberKey($serial)
    {

        $qb     = $this->em->createQueryBuilder();
        $select = $qb->select('a.memberKey')
            ->from(NetworkAccess::class, 'a')
            ->where('a.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($select)) {
            return $select[0]['memberKey'];
        }

        return false;
    }

    public function insertMember($memberKey, $memberId)
    {
        $member = new NetworkAccessMembers($memberId, $memberKey);

        $this->em->persist($member);
        $this->em->flush();
    }

    public function migrateMembers()
    {

        $fireNetworks = $this->firebase->getReference('dashboard/networks')->getSnapshot();
        $networks     = $fireNetworks->getValue();

        $memberNetwork = [];

        foreach ($networks as $key => $network) {

            if (array_key_exists('members', $network)) {
                $networkMember = [];
                foreach ($network['members'] as $memberUid => $val) {
                    if ($val === true) {
                        $networkMember[$memberUid] = true;
                        $memberKey                 = self::findMemberKey($key);
                        if ($memberKey !== false) {
                            self::insertMember($memberKey, $memberUid);
                        }
                    }
                }
                $memberNetwork[$key] = $networkMember;
            }
        }

        return [
            'status'  => 200,
            'message' => $memberNetwork
        ];
    }

    public function locationTypes()
    {
        $typeArr = [
            'Accounting',
            'Airport',
            'Amusement Park',
            'Aquarium',
            'Art Gallery',
            'Bakery',
            'Bank',
            'Bar',
            'Beauty Salon',
            'Book Store',
            'Bowling Alley',
            'Bus Station',
            'Cafe',
            'Campground',
            'Car Dealer',
            'Car Rental',
            'Car Repair',
            'Casino',
            'Church',
            'City Hall',
            'Clothing Store',
            'Convenience Store',
            'Dentist',
            'Department Store',
            'Doctor',
            'Electronics Store',
            'Embassy',
            'Florist',
            'Furniture Store',
            'Gym',
            'Hair Care',
            'Hardware Store',
            'Hindu Temple',
            'Home Goods Store',
            'Hospital',
            'Insurance Agency',
            'Jewelry Store',
            'Laundry',
            'Lawyer',
            'Library',
            'Liquor Store',
            'Local Government Office',
            'Locksmith',
            'Lodging',
            'Meal Delivery',
            'Meal Takeaway',
            'Mosque',
            'Movie Theater',
            'Museum',
            'Night Club',
            'Park',
            'Pet Store',
            'Pharmacy',
            'Physiotherapist',
            'Police',
            'Post Office',
            'Real Estate Agency',
            'Restaurant',
            'Rv Park',
            'Shoe Store',
            'Shopping Mall',
            'Spa',
            'Stadium',
            'Store',
            'Subway Station',
            'Synagogue',
            'Train Station',
            'Travel Agency',
            'University',
            'Veterinary Care',
            'Zoo'
        ];

        foreach ($typeArr as $type) {
            $newType       = new LocationTypes;
            $newType->name = $type;
            $this->em->persist($newType);
            $this->em->flush();
        }

        return true;
    }

    public function migrateBranding($serial = '')
    {
        /*$qb     = $this->em->createQueryBuilder();
        $select = $qb->select('u.branding')
            ->from(NetworkSettings::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();
        if (!empty($select)) {
            if (!is_null($select['branding'])) {
                foreach ($select['branding'] as $item) {
                    $newBranding                    = new LocationBranding;
                    $newBranding->background        = $item['background'];
                    $newBranding->boxShadow         = $item['box_shadow'];
                    $newBranding->footer            = $item['footer'];
                    $newBranding->header            = $item['header'];
                    $newBranding->input             = $item['input'];
                    $newBranding->currentBackground = $item['logo']['background']['current'];
                    $newBranding->lastBackground    = $item['logo']['background']['past'];
                    $this->em->persist($newBranding);
                    $this->em->flush();
                }

                return [
                    'status'  => 200,
                    'message' => $newBranding
                ];
            }
        }

        return [
            'status'  => 404,
            'message' => 'SERIAL_NOT_FOUND'
        ];*/
    }

    public function migratePaypal($offset)
    {
        $select = $this->em->createQueryBuilder()
            ->select('u.serial, u.alias, u.paypal_api_user, u.paypal_api_pass, u.paypal_api_sig')
            ->from(NetworkSettings::class, 'u')
            ->where('u.paypal_api_user IS NOT NULL')
            ->orderBy('u.id')
            ->setFirstResult($offset)
            ->setMaxResults(100);

        $results = new Paginator($select);
        $results->setUseOutputWalkers(false);

        $select = $results->getIterator()->getArrayCopy();

        if (empty($select)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($select) !== $return['total']) {
            $return['has_more'] = true;
        }


        foreach ($select as $paypal) {

            $ppAccountExists = $this->em->getRepository(PayPalAccount::class)->findOneBy([
                'username'  => $paypal['paypal_api_user'],
                'password'  => $paypal['paypal_api_pass'],
                'signature' => $paypal['paypal_api_sig']
            ]);

            $getAdmin = $this->em->getRepository(NetworkAccess::class)->findOneBy([
                'serial' => $paypal['serial']
            ]);

            if (is_null($getAdmin->admin)) {
                continue;
            }

            if (is_null($ppAccountExists)) {
                $ppAccountExists = new PayPalAccount(
                    'Account for ' . $paypal['alias'],
                    $paypal['paypal_api_user'],
                    $paypal['paypal_api_pass'],
                    $paypal['paypal_api_sig']
                );
                $this->em->persist($ppAccountExists);

                $this->em->flush();

                $ppAccountAccess = new PayPalAccountAccess($getAdmin->admin, $ppAccountExists->id);
                $this->em->persist($ppAccountAccess);

                $this->em->flush();
            }

            $this->em->createQueryBuilder()
                ->update(NetworkSettings::class, 'n')
                ->set('n.paypalAccount', ':account')
                ->where('n.serial = :serial')
                ->setParameter('account', $ppAccountExists->id)
                ->setParameter('serial', $paypal['serial'])
                ->getQuery()
                ->execute();
        }

        return Http::status(200, $return);
    }

    public function migrateImagesToS3($offset)
    {
        /*
        $settings = $this->em->createQueryBuilder()
            ->select('u')
            ->from(NetworkSettings::class, 'u')
            ->where('u.branding IS NOT NULL')
            ->setFirstResult($offset)
            ->setMaxResults(15)
            ->getQuery()
            ->getArrayResult();

        $failedToMigrateFile = 'failedToMigrate.txt';
        $response            = ['failed' => [], 'failed_bg' => [], 'success' => []];
        foreach ($settings as $key => $setting) {
            if (!array_key_exists('logo', $setting['branding'])) {
                continue;
            }
            if (!array_key_exists('background', $setting['branding']['logo'])) {
                $response['failed_bg'][] = $setting['serial'];
                continue;
            }
            if (!array_key_exists('header', $setting['branding']['logo'])) {
                $response['failed'][] = $setting['serial'];
                continue;
            }

            if (strpos($setting['branding']['logo']['background']['current'],
                    'https://blackbx.s3.eu-west-2.amazonaws.com') !== false) {
                continue;
            }

            if (strpos($setting['branding']['logo']['header']['current'],
                    'https://blackbx.s3.eu-west-2.amazonaws.com') !== false) {
                continue;
            }

            $backgroundPath = 'https://engine.blackbx.io/network/logo/' . $setting['serial'] . '/' . $setting['branding']['logo']['background']['current'];
            $headerPath     = 'https://engine.blackbx.io/network/logo/' . $setting['serial'] . '/' . $setting['branding']['logo']['header']['current'];


            $response['success'][] = $setting['serial'];
            $newLogo               = new _LogoUploadController($this->em, $this->firebase);
            $newLogo->saveImages($setting['serial'], ['header' => $headerPath, 'background' => $backgroundPath]);

        }

        return Http::status(200, $response);*/
    }

    public function migrateQuotePlans()
    {

        $qb     = $this->em->createQueryBuilder();
        $quotes = $qb
            ->select('u')
            ->from(PartnerQuotes::class, 'u')
            ->getQuery()
            ->getArrayResult();

        if (!empty($quotes)) {
            foreach ($quotes as $key => $value) {
                $items = $value['items'];
                foreach ($items as $plan) {

                    $serial = $plan['serial'];
                    $size   = strtoupper($plan['plan']);

                    if (array_key_exists('filtering', $plan)) {
                        if ((int)$plan['filtering'] === 1) {

                            $item = new PartnerQuoteItems($value['id'], $serial, 'CONTENT_FILTER', null);
                            $this->em->persist($item);
                            $this->em->flush();
                        }
                    }

                    $item = new PartnerQuoteItems($value['id'], $serial, $size, null);

                    $this->em->persist($item);
                    $this->em->flush();
                }
            }
        }

        return [
            'status'  => 200,
            'message' => 'MIGRATED'
        ];
    }

    public function enforceAdminsToHaveDefaultNotifications($offset)
    {
        $oauth = $this->em->createQueryBuilder()
            ->select('u.uid, u.email')
            ->from(OauthUser::class, 'u')
            ->where('u.role = :two')
            ->setParameter('two', 2)
            ->setFirstResult($offset)
            ->setMaxResults(100);

        $results = new Paginator($oauth);
        $results->setUseOutputWalkers(false);

        $oauth = $results->getIterator()->getArrayCopy();

        if (empty($oauth)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($oauth) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($oauth as $user) {
            $billingConnect               = new NotificationType($user['uid'], 'connect', 'billing_error');
            $billingEmail                 = new NotificationType($user['uid'], 'email', 'billing_error');
            $billingEmail->additionalInfo = $user['email'];

            $invoiceConnect               = new NotificationType($user['uid'], 'connect', 'billing_invoice_ready');
            $invoiceEmail                 = new NotificationType($user['uid'], 'email', 'billing_invoice_ready');
            $invoiceEmail->additionalInfo = $user['email'];

            $cardConnect               = new NotificationType($user['uid'], 'connect', 'card_expiry_reminder');
            $cardEmail                 = new NotificationType($user['uid'], 'email', 'card_expiry_reminder');
            $cardEmail->additionalInfo = $user['email'];

            $this->em->persist($billingConnect);
            $this->em->persist($billingEmail);
            $this->em->persist($invoiceConnect);
            $this->em->persist($invoiceEmail);
            $this->em->persist($cardConnect);
            $this->em->persist($cardEmail);
        }

        $this->em->flush();

        return Http::status(200, $return);
    }

    public function mergeScheduleRoute(Request $request, Response $response)
    {
        $offset = 0;

        if (array_key_exists('offset', $request->getQueryParams())) {
            $offset = $request->getQueryParams()['offset'];
        }

        $send = $this->mergeSchedule($offset);

        return $response->withJson($send, $send['status']);
    }

    public function mergeSchedule($offset)
    {
        /**
         * Get Old Schedule
         */

        $networkSettings = $this->em->createQueryBuilder()
            ->select('u.schedule, u.serial')
            ->from(NetworkSettings::class, 'u')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(50);

        $results = new Paginator($networkSettings);
        $results->setUseOutputWalkers(false);

        $networkSettings = $results->getIterator()->getArrayCopy();

        if (empty($networkSettings)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 50
        ];

        if ($offset <= $return['total'] && count($networkSettings) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($networkSettings as $networkSetting) {

            $scheduleId = $this->em->createQueryBuilder()
                ->select('u.schedule')
                ->from(LocationSettings::class, 'u')
                ->where('u.serial = :se')
                ->setParameter('se', $networkSetting['serial'])
                ->getQuery()
                ->getArrayResult()[0];

            $scheduleEnabled = false;

            if (array_key_exists('schedule', $networkSetting)) {
                if (!is_null($networkSetting['schedule'])) {
                    if (array_key_exists('enabled', $networkSetting['schedule'])) {
                        $scheduleEnabled = $networkSetting['schedule']['enabled'];
                    }

                    $schedule = $this->em->getRepository(LocationSchedule::class)->findOneBy([
                        'id' => $scheduleId['schedule']
                    ]);

                    if (is_null($schedule)) {
                        continue;
                    }

                    $schedule->enabled = $scheduleEnabled;

                    if (array_key_exists('days', $networkSetting['schedule'])) {
                        foreach ($networkSetting['schedule']['days'] as $key => $day) {
                            $enabled = false;
                            if (array_key_exists('enabled', $day)) {
                                if (!is_null($day['enabled'])) {
                                    $enabled = $day['enabled'];
                                }
                            }
                            $newDay = new LocationScheduleDay($enabled, $schedule->id, $key);
                            $this->em->persist($newDay);
                            $newTime        = new LocationScheduleTime($newDay->id);
                            $newTime->open  = $day['open'];
                            $newTime->close = $day['close'];
                            $this->em->persist($newTime);
                        }
                    } else {
                        for ($i = 0; $i <= 6; $i++) {
                            $newDay = new LocationScheduleDay(false, $schedule->id, $i);
                            $this->em->persist($newDay);
                        }
                    }
                }
            }
        }

        $this->em->flush();


        return Http::status(200, $return);
    }

    public function normalizeNetworkSettings($offset)
    {

        $networkSettings = $this->em->createQueryBuilder()
            ->select('
            u.id,
            u.alias,
            u.serial, 
            u.branding, 
            u.other, 
            u.wifi, 
            u.location, 
            u.facebook, 
            u.schedule, 
            u.hideFooter, 
            u.message,
            u.url,
            u.freeQuestions,
            u.customQuestions,
            u.type,
            u.currency,
            u.translation,
            u.language,
            u.stripe_connect_id,
            u.paymentType,
            u.createdAt,
            u.paypalAccount')
            ->from(NetworkSettings::class, 'u')
            ->orderBy('u.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(50);

        $results = new Paginator($networkSettings);
        $results->setUseOutputWalkers(false);

        $networkSettings = $results->getIterator()->getArrayCopy();

        if (empty($networkSettings)) {
            return Http::status(204);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 50
        ];

        if ($offset <= $return['total'] && count($networkSettings) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($networkSettings as $networkSetting) {

            $alreadyMigratedCheck = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(LocationSettings::class, 'u')
                ->where('u.serial = :ser')
                ->setParameter('ser', $networkSetting['serial'])
                ->getQuery()
                ->getArrayResult();

            if (!empty($alreadyMigratedCheck)) {
                continue;
            }

            $firebaseRef     = $this->firebase->getReference('dashboard/networks/' . $networkSetting['serial'])->getValue();
            $page            = '';
            $facebookEnabled = false;

            if (array_key_exists('facebook', $networkSetting)) {
                if (!is_null($networkSetting['facebook'])) {
                    if (array_key_exists('page_id', $networkSetting['facebook'])) {
                        $page = $networkSetting['facebook']['page_id'];
                    }

                    if (array_key_exists('enabled', $networkSetting['facebook'])) {
                        $facebookEnabled = $networkSetting['facebook']['enabled'];
                    }
                }
            }

            $newSocial = new LocationSocial($facebookEnabled, 'facebook', $page);
            $this->em->persist($newSocial);

            $validation = LocationOther::defaultValidation();
            $limit      = LocationOther::defaultHybridLimit();
            $optText    = LocationOther::defaultOptText();

            if (array_key_exists('other', $networkSetting)) {

                if (!is_null($networkSetting['other'])) {
                    if (array_key_exists('validation', $networkSetting['other'])) {
                        $validation = $networkSetting['other']['validation'];
                    }

                    if (array_key_exists('limit', $networkSetting['other'])) {
                        $limit = $networkSetting['other']['limit'];
                    }

                    if (array_key_exists('opt_text', $networkSetting['other'])) {
                        $optText = $networkSetting['other']['opt_text'];
                    }
                }
            }


            $newOther = new LocationOther($validation, $limit, $optText);
            if (!is_null($networkSetting['other'])) {
                if (array_key_exists('validation_timeout', $networkSetting['other'])) {
                    $newOther->validationTimeout = $networkSetting['other']['validation_timeout'];
                }

                if (array_key_exists('opt_checked', $networkSetting['other'])) {
                    $newOther->optChecked = $networkSetting['other']['opt_checked'];
                }

                if (array_key_exists('opt_required', $networkSetting['other'])) {
                    $newOther->optRequired = $networkSetting['other']['opt_required'];
                }
            }

            $newOther->allowSpamEmails    = LocationOther::defaultAllowSpamEmails();
            $newOther->onlyBusinessEmails = LocationOther::defaultOnlyBusinessEmails();

            $this->em->persist($newOther);

            $freeIdle    = LocationTimeout::defaultFreeIdle();
            $freeSession = LocationTimeout::defaultFreeSession();

            $paidIdle    = LocationTimeout::defaultPaidIdle();
            $paidSession = LocationTimeout::defaultFreeSession();


            if (array_key_exists('settings', $firebaseRef)) {
                if (array_key_exists('timeouts', $firebaseRef['settings'])) {
                    if (array_key_exists('free', $firebaseRef['settings']['timeouts'])) {

                        if (array_key_exists('idle', $firebaseRef['settings']['timeouts']['free'])) {
                            if ($firebaseRef['settings']['timeouts']['free']['idle'] !== 0) {
                                $freeIdle = $firebaseRef['settings']['timeouts']['free']['idle'];
                            }
                        }

                        if (array_key_exists('session', $firebaseRef['settings']['timeouts']['free'])) {
                            if ($firebaseRef['settings']['timeouts']['free']['session'] !== 0) {
                                $freeSession = $firebaseRef['settings']['timeouts']['free']['session'];
                            }
                        }
                    }


                    if (array_key_exists('paid', $firebaseRef['settings']['timeouts'])) {
                        if (array_key_exists('idle', $firebaseRef['settings']['timeouts']['paid'])) {
                            if ($firebaseRef['settings']['timeouts']['paid']['idle'] !== 0) {
                                $paidIdle = $firebaseRef['settings']['timeouts']['paid']['idle'];
                            }
                        }

                        if (array_key_exists('session', $firebaseRef['settings']['timeouts']['paid'])) {
                            if ($firebaseRef['settings']['timeouts']['paid']['session'] !== 0) {
                                $paidSession = $firebaseRef['settings']['timeouts']['paid']['session'];
                            }
                        }
                    }
                }
            }

            $newFreeTimeOut = new LocationTimeout(
                $freeIdle,
                $freeSession,
                $newOther->id,
                'free'
            );
            $this->em->persist($newFreeTimeOut);

            $newPaidTimeOut = new LocationTimeout(
                $paidIdle,
                $paidSession,
                $newOther->id,
                'paid'
            );
            $this->em->persist($newPaidTimeOut);


            $freeDownload = LocationBandwidth::defaultFreeDownload();
            $freeUpload   = LocationBandwidth::defaultFreeUpload();

            $paidDownload = LocationBandwidth::defaultPaidDownload();
            $paidUpload   = LocationBandwidth::defaultPaidUpload();


            if (array_key_exists('settings', $firebaseRef)) {
                if (array_key_exists('bandwidth', $firebaseRef['settings'])) {
                    if (array_key_exists('free', $firebaseRef['settings']['bandwidth'])) {
                        if (array_key_exists('download', $firebaseRef['settings']['bandwidth']['free'])) {

                            if ($firebaseRef['settings']['bandwidth']['free']['download'] !== 0) {
                                $freeDownload = $firebaseRef['settings']['bandwidth']['free']['download'];
                            }
                        }

                        if (array_key_exists('upload', $firebaseRef['settings']['bandwidth']['free'])) {
                            if ($firebaseRef['settings']['bandwidth']['free']['upload'] !== 0) {
                                $freeUpload = $firebaseRef['settings']['bandwidth']['free']['upload'];
                            }
                        }
                    }

                    if (array_key_exists('paid', $firebaseRef['settings']['bandwidth'])) {
                        if (array_key_exists('download', $firebaseRef['settings']['bandwidth']['paid'])) {
                            if ($firebaseRef['settings']['bandwidth']['paid']['download'] !== 0) {
                                $paidDownload = $firebaseRef['settings']['bandwidth']['paid']['download'];
                            }
                        }

                        if (array_key_exists('upload', $firebaseRef['settings']['bandwidth']['paid'])) {
                            if ($firebaseRef['settings']['bandwidth']['paid']['upload'] !== 0) {
                                $paidUpload = $firebaseRef['settings']['bandwidth']['paid']['upload'];
                            }
                        }
                    }
                }
            }

            $newFreeBandwidth = new LocationBandwidth(
                $freeDownload,
                $freeUpload,
                $newOther->id,
                'free'
            );
            $this->em->persist($newFreeBandwidth);

            $newPaidBandwidth = new LocationBandwidth(
                $paidDownload,
                $paidUpload,
                $newOther->id,
                'paid'
            );
            $this->em->persist($newPaidBandwidth);


            $wifiDisabled = true;
            $ssid         = $networkSetting['serial'];

            if (array_key_exists('wifi', $networkSetting)) {
                if (!is_null($networkSetting['wifi'])) {
                    if (array_key_exists('disabled', $networkSetting['wifi'])) {
                        $wifiDisabled = $networkSetting['wifi']['disabled'];
                    }

                    if (array_key_exists('ssid', $networkSetting['wifi'])) {
                        $ssid = $networkSetting['wifi']['ssid'];
                    }
                }
            }

            $newWifi = new LocationWiFi($wifiDisabled, $ssid);
            $this->em->persist($newWifi);

            $lat  = LocationPosition::defaultLat();
            $lng  = LocationPosition::defaultLng();
            $name = LocationPosition::defaultFormattedAddress();

            if (array_key_exists('location', $networkSetting)) {
                if (!is_null($networkSetting['location'])) {
                    if (array_key_exists('lat', $networkSetting['location'])) {
                        if (!is_null($networkSetting['location']['lat'])) {
                            $lat = (float)$networkSetting['location']['lat'];
                        }
                    }

                    if (array_key_exists('lng', $networkSetting['location'])) {
                        if (!is_null($networkSetting['location']['lng'])) {
                            $lng = (float)$networkSetting['location']['lng'];
                        }
                    }

                    if (array_key_exists('name', $networkSetting['location'])) {
                        $name = $networkSetting['location']['name'];
                    }
                }
            }

            $newPosition = new LocationPosition(
                $lat,
                $lng,
                $name,
                '',
                '',
                '',
                '',
                '',
                ''
            );
            $this->em->persist($newPosition);

            $headerTopRadius      = LocationBranding::defaultHeaderTopRadius();
            $roundFormTopLeft     = LocationBranding::defaultRoundFormTopLeft();
            $roundFormTopRight    = LocationBranding::defaultRoundFormTopRight();
            $roundFormBottomLeft  = LocationBranding::defaultRoundFormBottomLeft();
            $roundFormBottomRight = LocationBranding::defaultRoundFormBottomRight();
            $roundInputs          = LocationBranding::defaultRoundInputs();
            $headerColor          = LocationBranding::defaultHeaderColor();
            $headerLogoPadding    = LocationBranding::defaultHeaderLogoPadding();
            $customCss            = null;
            $background           = LocationBranding::defaultBackground();
            $boxShadow            = LocationBranding::defaultBoxShadow();
            $footer               = LocationBranding::defaultFooter();
            $input                = LocationBranding::defaultInput();
            $backgroundImage      = LocationBranding::defaultBackgroundImage();
            $headerImage          = LocationBranding::defaultHeaderImage();
            $primary              = LocationBranding::defaultPrimary();
            $textColor            = LocationBranding::defaultTextColor();
            $hideFooter           = LocationBranding::defaultHideFooter();

            if (!is_null($networkSetting['branding'])) {
                if (array_key_exists('background', $networkSetting['branding'])) {
                    $background = $networkSetting['branding']['background'];
                }

                if (array_key_exists('boxShadow', $networkSetting['branding'])) {
                    $boxShadow = $networkSetting['branding']['boxShadow'];
                }

                if (array_key_exists('footer', $networkSetting['branding'])) {
                    $footer = $networkSetting['branding']['footer'];
                }

                if (array_key_exists('headerTopRadius', $networkSetting['branding'])) {
                    $headerTopRadius = $networkSetting['branding']['headerTopRadius'];
                }

                if (array_key_exists('roundFormTopLeft', $networkSetting['branding'])) {
                    $roundFormTopLeft = $networkSetting['branding']['roundFormTopLeft'];
                }

                if (array_key_exists('roundFormTopRight', $networkSetting['branding'])) {
                    $roundFormTopRight = $networkSetting['branding']['roundFormTopRight'];
                }

                if (array_key_exists('roundFormBottomLeft', $networkSetting['branding'])) {
                    $roundFormBottomLeft = $networkSetting['branding']['roundFormBottomLeft'];
                }

                if (array_key_exists('roundFormBottomRight', $networkSetting['branding'])) {
                    $roundFormBottomRight = $networkSetting['branding']['roundFormBottomRight'];
                }

                if (array_key_exists('roundInputs', $networkSetting['branding'])) {
                    $roundInputs = $networkSetting['branding']['roundInputs'];
                }

                if (array_key_exists('headerColor', $networkSetting['branding'])) {
                    $headerColor = $networkSetting['branding']['headerColor'];
                }

                if (array_key_exists('headerLogoPadding', $networkSetting['branding'])) {
                    if ($networkSetting['branding']['headerLogoPadding'] === true) {
                        $headerLogoPadding = true;
                    }
                }

                if (array_key_exists('input', $networkSetting['branding'])) {
                    $input = $networkSetting['branding']['input'];
                }

                if (array_key_exists('branding', $networkSetting)) {
                    if (array_key_exists('logo', $networkSetting['branding'])) {
                        if (array_key_exists('background', $networkSetting['branding']['logo'])) {
                            if (array_key_exists('current', $networkSetting['branding']['logo']['background'])) {
                                $backgroundImage = $networkSetting['branding']['logo']['background']['current'];
                            }
                        }
                    }
                }

                if (array_key_exists('branding', $networkSetting)) {
                    if (array_key_exists('logo', $networkSetting['branding'])) {
                        if (array_key_exists('header', $networkSetting['branding']['logo'])) {
                            if (array_key_exists('current', $networkSetting['branding']['logo']['header'])) {
                                $headerImage = $networkSetting['branding']['logo']['header']['current'];
                            }
                        }
                    }
                }

                if (array_key_exists('primary', $networkSetting['branding'])) {
                    $primary = $networkSetting['branding']['primary'];
                }

                if (array_key_exists('text_color', $networkSetting['branding'])) {
                    $textColor = $networkSetting['branding']['text_color'];
                }

                if (array_key_exists('hideFooter', $networkSetting)) {
                    $hideFooter = $networkSetting['hideFooter'];
                }


                if (array_key_exists('css', $networkSetting['branding'])) {
                    $cssFileContents = str_replace('\n', '', $networkSetting['branding']['css']);
                    $cssFile         = fopen('cssFile.css', 'w');
                    fwrite($cssFile, $cssFileContents);
                    fclose($cssFile);

                    $customCss = $this->s3->upload(
                        'locations/' . $networkSetting['serial'] . '/branding/css/custom.css',
                        'string',
                        'cssFile.css',
                        'public-read',
                        [
                            'CacheControl' => 'max-age=3600',
                            'ContentType'  => 'text/css'
                        ]
                    );
                    //unlink($cssFile);
                }
            }


            $newBranding = new LocationBranding(
                $background,
                $boxShadow,
                $footer,
                $headerLogoPadding,
                $headerTopRadius,
                $headerColor,
                $input,
                $backgroundImage,
                $headerImage,
                $primary,
                $roundFormTopLeft,
                $roundFormTopRight,
                $roundFormBottomLeft,
                $roundFormBottomRight,
                $textColor,
                $roundInputs,
                $hideFooter
            );

            if (!empty($networkSetting['message'])) {
                $newBranding->message = $networkSetting['message'];
            }

            $newBranding->interfaceColor = 'rgba(32, 160, 255,1.0)';

            if (!is_null($customCss)) {
                $newBranding->customCSS = $customCss;
            }

            $this->em->persist($newBranding);

            $newSchedule = new LocationSchedule();

            $scheduleEnabled = false;
            if (array_key_exists('schedule', $networkSetting)) {
                if (!is_null($networkSetting['schedule'])) {
                    if (array_key_exists('enabled', $networkSetting['schedule'])) {
                        $scheduleEnabled = $networkSetting['schedule']['enabled'];
                    }
                    $newSchedule->enabled = $scheduleEnabled;
                    $this->em->persist($newSchedule);

                    if (array_key_exists('days', $networkSetting['schedule'])) {
                        foreach ($networkSetting['schedule']['days'] as $key => $day) {
                            $newDay = new LocationScheduleDay($day['enabled'], $newSchedule->id, $key);
                            $this->em->persist($newDay);
                            $newTime        = new LocationScheduleTime($newDay->id);
                            $newTime->open  = $day['open'];
                            $newTime->close = $day['close'];
                            $this->em->persist($newTime);
                        }
                    } else {
                        for ($i = 0; $i <= 6; $i++) {
                            $newDay = new LocationScheduleDay(false, $newSchedule->id, $i);
                            $this->em->persist($newDay);
                        }
                    }
                }
            }


            $url             = LocationSettings::defaultUrl();
            $freeQuestions   = ['Email'];
            $customQuestions = [];
            $type            = LocationSettings::defaultType();
            $currency        = 'GBP';
            $translation     = false;
            $language        = 'en';
            $stripeConnectId = '';
            $paymentType     = '';
            $createdAt       = new \DateTime();
            $paypalAccount   = null;

            if (array_key_exists('url', $networkSetting)) {
                $url = $networkSetting['url'];
            }

            if (array_key_exists('freeQuestions', $networkSetting)) {
                $freeQuestions = $networkSetting['freeQuestions'];
            }

            if (array_key_exists('customQuestions', $networkSetting)) {
                $customQuestions = $networkSetting['customQuestions'];
            }

            if (array_key_exists('currency', $networkSetting)) {
                $currency = $networkSetting['currency'];
            }

            if (array_key_exists('type', $networkSetting)) {
                $type = $networkSetting['type'];
            }

            if (array_key_exists('translation', $networkSetting)) {
                $translation = $networkSetting['translation'];
            }

            if (array_key_exists('language', $networkSetting)) {
                $language = $networkSetting['language'];
            }

            if (array_key_exists('stripe_connect_id', $networkSetting)) {
                $stripeConnectId = $networkSetting['stripe_connect_id'];
            }

            if (array_key_exists('paymentType', $networkSetting)) {
                $paymentType = $networkSetting['paymentType'];
            }

            if (array_key_exists('createdAt', $networkSetting)) {
                if (!is_null($networkSetting['createdAt'])) {
                    $createdAt = $networkSetting['createdAt'];
                }
            }

            if (array_key_exists('paypalAccount', $networkSetting)) {
                if (!is_null($networkSetting['paypalAccount'])) {
                    $paypalAccount = $networkSetting['paypalAccount'];
                }
            }

            $alias = null;

            if (array_key_exists('alias', $networkSetting)) {
                $alias = $networkSetting['alias'];
            }

            $newLocation = new LocationSettings(
                $networkSetting['serial'],
                $newOther->id,
                $newBranding->id,
                $newWifi->id,
                $newPosition->id,
                $newSocial->id,
                $newSchedule->id,
                $url,
                $freeQuestions
            );

            $newLocation->alias             = $alias;
            $newLocation->customQuestions   = $customQuestions;
            $newLocation->type              = $type;
            $newLocation->currency          = $currency;
            $newLocation->translation       = $translation;
            $newLocation->language          = $language;
            $newLocation->stripe_connect_id = $stripeConnectId;
            $newLocation->paymentType       = $paymentType;
            $newLocation->createdAt         = $createdAt;

            if (!is_null($paypalAccount)) {
                $newLocation->paypalAccount = $paypalAccount;
            }

            $this->em->persist($newLocation);

            $this->em->flush();
        }


        return Http::status(200, $return);
    }

    public function migrateEverythingToHtmlTemplateType()
    {
        $messages = $this->em->createQueryBuilder()
            ->select('u.emailContents, u.id')
            ->from(MarketingMessages::class, 'u')
            ->where('u.sendToEmail = :true')
            ->andWhere('u.templateType IN (:invalidTypes)')
            ->setParameter('true', true)
            ->setParameter('invalidTypes', ['companyTheme', 'announcementTheme', 'plainTheme'])
            ->getQuery()
            ->getArrayResult();

        foreach ($messages as $message) {
            $template = $this->view->render('Emails/MarketingHTMLTemplate/html.twig', ['message' => $message['emailContents']]);

            $this->em->createQueryBuilder()
                ->update(MarketingMessages::class, 'u')
                ->set('u.emailContents', ':contents')
                ->set('u.templateType', ':html')
                ->where('u.id = :id')
                ->setParameter('contents', $template)
                ->setParameter('id', $message['id'])
                ->setParameter('html', 'html')
                ->getQuery()
                ->execute();
        }


        return Http::status(200);
    }
}
