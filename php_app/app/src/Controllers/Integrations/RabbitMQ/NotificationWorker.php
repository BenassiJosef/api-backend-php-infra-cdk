<?php

/**
 * Created by jamieaitken on 18/06/2018 at 11:13
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Models\Notifications\Notification;
use App\Package\Organisations\OrganizationService;
use App\Utils\PushNotifications;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Controllers\Integrations\Hooks\_HooksController;
use App\Package\Reviews\ReviewSentiment;

class NotificationWorker
{

    private $em;
    private $hooks;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->hooks = new _HooksController($this->em);
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $notify = new PushNotifications($this->em);

        $notification = new Notification(
            $body['notificationContent']['objectId'],
            $body['notificationContent']['title'],
            $body['notificationContent']['kind'],
            $body['notificationContent']['link']
        );

        $this->em->persist($notification);
        $this->em->flush();

        $message = null;
        $serial = null;
        $orgId = null;

        if (isset($body['notificationContent']['message'])) {
            $message = $body['notificationContent']['message'];
            $notification->setMessage($message);
        }

        if (isset($body['notificationContent']['campaignId'])) {
            $notification->setParamsRoute(['campaignId' => $body['notificationContent']['campaignId']]);
        }
        if (isset($body['notificationContent']['profileId'])) {
            $notification->setParamsRoute(['profileId' => $body['notificationContent']['profileId']]);
        }
        if (isset($body['notificationContent']['giftId'])) {
            $notification->setParamsRoute(['giftId' => $body['notificationContent']['giftId']]);
        }
        if (isset($body['notificationContent']['cardId'])) {
            $notification->setParamsRoute(['cardId' => $body['notificationContent']['cardId']]);
        }

        if (isset($body['notificationContent']['orgId'])) {
            $notification->setOrgId(($body['notificationContent']['orgId']));
            $notification->setParamsRoute(['orgId' => $body['notificationContent']['orgId']]);
            $orgId = $body['notificationContent']['orgId'];
        }

        if (isset($body['notificationContent']['serial'])) {
            $notification->serial = $body['notificationContent']['serial'];
            $notification->setParamsRoute(['serial' => $serial]);
            $serial = $notification->serial;
        }

        if ($notification->getKind() === 'review_received') {
            $sentimentController = new ReviewSentiment($this->em);
            $sentimentController->sentimentForReview(
                $body['notificationContent']['objectId']
            );
            $notification->setParamsRoute(['reviewId' => $body['notificationContent']['objectId']]);
            $notification->setParamsRoute(['review' => $body['data']]);
        }

        if (!is_null($orgId)) {
            $users = $notify->getMembersByOrgId($orgId, $notification->kind);
            foreach ($users as $key => $user) {
                $notify->pushNotification($notification, 'specific', ['uid' => $user['uid']], $message);
            }

            return true;
        }



        if (!is_null($serial)) {
            $alerts = $notify->getMembersViaSerial($serial, $notification->kind);
            foreach ($alerts as $key => $user) {
                $notify->pushNotification($notification, 'specific', ['uid' => $user['uid']], $message);
            }

            return true;
        }

        $notify->pushNotification(
            $notification,
            $body['pushMethod']['kind'],
            ['uid' => $body['pushMethod']['uid']],
            $message
        );

        return true;
    }
}
