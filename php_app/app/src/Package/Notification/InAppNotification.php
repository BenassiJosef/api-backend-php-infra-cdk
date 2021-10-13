<?php

namespace App\Package\Notification;

use App\Models\Notifications\FCMNotificationTokens;
use App\Models\Notifications\FirebaseNotification;
use Curl\Curl;
use Doctrine\ORM\EntityManager;

class InAppNotification
{

    public static $awsApiKey = 'qBKGfkexpL9mwsVECigLX9NyASU3ymxQ5SOYWOvy';
    public static $awsEndPoint = 'https://notification.stampede.ai/in-app';

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * FirebaseNotification constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function sendNotification(FirebaseNotification $notification, FCMNotificationTokens $fbtoken)
    {
        $request = new Curl();
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('x-api-key', $this::$awsApiKey);

        $request->post($this::$awsEndPoint, $notification->getFirebaseNotification());

        if ($request->error) {
            $this->entityManager->remove($fbtoken);
            $this->entityManager->flush();
        }
    }

    public function sendNotificationToProfile(string $uid, FirebaseNotification $notification)
    {
/**
 * @var FCMNotificationTokens[]  $tokens
 */
        $tokens = $this->entityManager->getRepository(FCMNotificationTokens::class)->findBy([
            'uid' => $uid,
        ]);

        if (is_null($tokens)) {
            return;
        }
        foreach ($tokens as $token) {
            $notification->setToken($token->getToken());
            $this->sendNotification($notification, $token);
        }
    }
}
