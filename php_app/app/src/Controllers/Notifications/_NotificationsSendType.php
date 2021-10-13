<?php
/**
 * Created by jamieaitken on 27/11/2017 at 12:13
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Notifications;

use App\Controllers\Branding\_BrandingController;
use App\Models\Notifications\NotificationType;
use App\Models\OauthUser;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _NotificationsSendType
{
    protected $em;
    private $connectCache;

    private $allowedInsightUserNotifications = [
        'insight_daily',
        'insight_weekly',
        'insight_biWeekly',
        'insight_monthly',
        'insight_biMonthly'
    ];

    private $allowedModeratorUserNotifications = [
        'network_online',
        'network_offline',
        'capture_connected',
        'capture_registered',
        'capture_validated',
        'capture_payment',
        'review_received',
        'gift_card',
        'campaign'
        //'capture_return',
    ];

    private $allowedAdminUserNotifications = [
        'billing_error',
        'billing_invoice_ready',
        'card_expiry_reminder'
    ];


    public $whiteLabelCantHaveNotifications = [
        'feature_approved',
        'feature_completed'
    ];

    public $notificationTypes = [
        'slack',
        'email',
        'sms',
        'connect',
        'inApp'
    ];

    public $immutableNotifications = [
        'billing_error',
        'billing_invoice_ready',
        'card_expiry_reminder'
    ];

    public $leastType = [
        'connect',
        'email'
    ];

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('accessUser'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateViaKeyRoute(Request $request, Response $response)
    {
        $send = $this->updateViaKey($request->getParsedBody(), $request->getAttribute('accessUser'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete($request->getAttribute('accessUser'), $request->getQueryParams()['id']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    /**
     * @param array $user
     * @return array
     */

    public function get(array $user)
    {

        $fetch = $this->connectCache->fetch($user['uid'] . ':notificationList');
        if (!is_bool($fetch)) {
            return Http::status(200, $fetch);
        }

        $select = $this->em->getRepository(NotificationType::class)->findBy([
            'uid' => $user['uid']
        ]);

        $userPartnerController = new _BrandingController($this->em);
        $userPartner           = [];
        if ($user['role'] >= 3) {
            $admin       = $this->em->createQueryBuilder()
                ->select('u.admin')
                ->from(OauthUser::class, 'u')
                ->where('u.uid = :ui')
                ->setParameter('ui', $user['uid'])
                ->getQuery()
                ->getArrayResult();
            $userPartner = $userPartnerController->fromAdmin($admin[0]['admin']);
        }

        $frontend = $this->frontEndModel($user, $userPartner);


        foreach ($select as $key => $item) {

            $frontend[$item->notificationKind]['types'][$item->type]['id']             = $item->id;
            $frontend[$item->notificationKind]['types'][$item->type]['additionalInfo'] = $item->additionalInfo;

            if (!is_null($item->id)) {
                $frontend[$item->notificationKind]['types'][$item->type]['enabled'] = true;
            }

            if (in_array($item->notificationKind, $this->immutableNotifications)) {
                if (in_array($item->type, $this->leastType)) {
                    $frontend[$item->notificationKind]['types'][$item->type]['immutable'] = true;
                }
            }
        }

        $this->connectCache->save($user['uid'] . ':notificationList', $frontend);

        return Http::status(200, $frontend);
    }

    public function updateViaKey(array $body, array $user)
    {

        if (in_array($body['type'], $this->leastType) && in_array($body['kind'],
                $this->immutableNotifications)) {
            return Http::status(409, 'CAN_NOT_EDIT');
        }

        $lookUp = $this->em->getRepository(NotificationType::class)->findOneBy([
            'uid'              => $user['uid'],
            'type'             => $body['type'],
            'notificationKind' => $body['kind']
        ]);

        if ($body['enabled'] === true) {
            if (is_null($lookUp)) {
                $newKind                 = new NotificationType($user['uid'], $body['type'], $body['kind']);
                $newKind->additionalInfo = $body['additionalInfo'];
                $this->em->persist($newKind);
            }
        } elseif ($body['enabled'] === false) {
            if (is_object($lookUp)) {
                $this->em->remove($lookUp);
            }
        }


        $this->em->flush();

        $this->connectCache->delete($user['uid'] . ':notificationList');

        return Http::status(200, $this->get($user)['message']);
    }

    public function delete(array $user, string $id)
    {
        $getUser = $this->em->getRepository(NotificationType::class)->findOneBy([
            'uid' => $user['uid'],
            'id'  => $id
        ]);

        if (is_object($getUser)) {


            $this->em->remove($getUser);
            $this->em->flush();
        }

        return Http::status(200);
    }

    public function frontEndModel(array $user, $partnerBranding)
    {
        $nicer        = [];
        $defaultTypes = [];
        foreach ($this->notificationTypes as $type) {

            $structure = [
                'id'             => null,
                'enabled'        => false,
                'additionalInfo' => null,
                'immutable'      => false
            ];

            if ($type === 'connect') {
                $defaultTypes[$type] = [
                    'id'             => null,
                    'enabled'        => true,
                    'additionalInfo' => null,
                    'immutable'      => true
                ];
            }

            $defaultTypes[$type] = $structure;
        }

        if ($user['role'] === 4) {
            foreach ($this->allowedInsightUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
        } elseif ($user['role'] === 3) {
            foreach ($this->allowedInsightUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
            foreach ($this->allowedModeratorUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
        } elseif ($user['role'] === 2) {
            foreach ($this->allowedInsightUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
            foreach ($this->allowedModeratorUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
            foreach ($this->allowedAdminUserNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
        }

        if (empty($partnerBranding) || $partnerBranding['name'] === 'Stampede') {
            foreach ($this->whiteLabelCantHaveNotifications as $notification) {
                $nicer[$notification] = [
                    'types' => $defaultTypes
                ];
            }
        }

        return $nicer;
    }

}