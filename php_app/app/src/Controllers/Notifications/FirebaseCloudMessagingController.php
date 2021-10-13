<?php

/**
 * Created by jamieaitken on 24/09/2018 at 11:13
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Notifications;

use App\Models\Notifications\FCMNotificationTokens;
use App\Models\Notifications\Notification;
use App\Models\Notifications\NotificationType;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class FirebaseCloudMessagingController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function sendMessage(string $uid, Notification $notification)
    {

        $doesUserWantToReceive = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(NotificationType::class, 'u')
            ->where('u.uid = :uid')
            ->andWhere('u.notificationKind = :notificationKind')
            ->andWhere('u.type = :fcm')
            ->setParameter('uid', $uid)
            ->setParameter('notificationKind', $notification->kind)
            ->setParameter('fcm', 'inApp')
            ->getQuery()
            ->getArrayResult();

        if (empty($doesUserWantToReceive)) {
            return;
        }

        $deviceTokens = $this->getToken($uid);

        if ($deviceTokens['status'] !== 200) {
            return;
        }

        $notificationTitle = strpos($notification->kind, 'insight_') === false ? ucfirst(str_replace(
            '_',
            ' ',
            $notification->kind
        )) : $notification->title;

        foreach ($deviceTokens['message'] as $deviceToken) {
            $request = new Curl();
            $request->setHeader('Content-Type', 'application/json');
            $request->setHeader('x-api-key', 'qBKGfkexpL9mwsVECigLX9NyASU3ymxQ5SOYWOvy');

            $request->post('https://notification.stampede.ai/in-app', [
                'token' => $deviceToken['token'],
                'notification' => [
                    'title' => $notificationTitle,
                    'body' => empty($notification->getFCMMessage()) ? $notification->getMessage() : $notification->getFCMMessage(),
                ],
                'data' => [
                    'route' => $notification->getRoute(),
                    'params' => array_merge(['params' => $notification->getFCMParams()], $notification->getFCMParams()),
                ],
                'webpush' => [
                    'fcm_options' => [
                        'link' => 'https://product.stampede.ai/' . $notification->getProductRoute(),
                    ],
                ],
            ]);
            if ($request->httpStatusCode === 502) {
                $findOneViaToken = $this->em->getRepository(FCMNotificationTokens::class)->findOneBy([
                    'token' => $deviceToken['token'],
                ]);

                if (is_object($findOneViaToken)) {
                    $this->em->remove($findOneViaToken);
                }
            }
        }

        $this->em->flush();
    }

    public function createUpdateTokenRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->createUpdateToken($body, $request->getAttribute('user')['uid']);

        return $response->withJson($send, $send['status']);
    }

    public function getTokenRoute(Request $request, Response $response)
    {
        $send = $this->getToken($request->getAttribute('user')['uid']);

        return $response->withJson($send, $send['status']);
    }

    public function createUpdateToken(array $body, string $uid)
    {
        if (!isset($body['token'])) {
            return Http::status(400, 'REQUIRES_TOKEN');
        }

        if (!isset($body['instanceId'])) {
            return Http::status(400, 'REQUIRES_INSTANCE_ID');
        }

        $updating = $this->em->getRepository(FCMNotificationTokens::class)->findOneBy([
            'uid' => $uid,
            'instanceId' => $body['instanceId'],
        ]);

        if (is_object($updating)) {
            $updating->token = $body['token'];
        } else {
            $updating = new FCMNotificationTokens($uid, $body['token'], $body['instanceId']);
            $this->em->persist($updating);
        }

        $this->em->flush();

        return Http::status(200);
    }

    public function getToken(string $uid)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.token, u.instanceId')
            ->from(FCMNotificationTokens::class, 'u')
            ->where('u.uid = :uid')
            ->setParameter('uid', $uid)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'USER_HAS_NO_TOKEN');
        }

        return Http::status(200, $get);
    }
}
