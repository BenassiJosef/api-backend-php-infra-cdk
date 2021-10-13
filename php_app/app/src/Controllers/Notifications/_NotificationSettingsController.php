<?php
/**
 * Created by jamieaitken on 23/11/2017 at 11:41
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Notifications;

use App\Models\Notifications\NotificationSettings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _NotificationSettingsController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('uid'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $send = $this->update($request->getAttribute('uid'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $uid)
    {
        $notifications = $this->em->createQueryBuilder()
            ->select('u.notificationKind')
            ->from(NotificationSettings::class, 'u')
            ->where('u.uid = :uid')
            ->andWhere('u.hidden = :false')
            ->setParameter('uid', $uid)
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $notifications);
    }

    public function update(string $uid, array $body)
    {
        $allowedNotifications = [
            'marketing_campaign',
            'capture_connected',
            'capture_registered',
            'capture_return',
            'capture_validated',
            'capture_payment',
            'feature_approved',
            'feature_completed',
            'insight_daily',
            'insight_weekly',
            'insight_biWeekly',
            'insight_monthly',
            'insight_biMonthly',
            'network_online',
            'network_offline',
            'review_received',
            'gift_card',
            'campaign'
        ];

        $editable = $this->em->getRepository(NotificationSettings::class)->findBy([
            'uid'    => $uid,
            'hidden' => false
        ]);

        foreach ($editable as $value) {
            $this->em->remove($value);
        }

        $this->em->flush();

        foreach ($body['notifications'] as $notification) {
            if (in_array($notification, $allowedNotifications)) {
                $newSetting = new NotificationSettings($uid, $notification);
                $this->em->persist($newSetting);
            }
        }

        $this->em->flush();

        return Http::status(200);
    }
}