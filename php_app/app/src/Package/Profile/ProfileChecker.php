<?php

namespace App\Package\Profile;

use App\Controllers\Auth\_oAuth2TokenController;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileAccountService;
use App\Models\Notifications\FCMNotificationTokens;
use App\Models\UserProfile;
use App\Models\User\UserAccount;
use Doctrine\ORM\EntityManager;
use OAuth2\Server;
use Slim\Http\Request;
use Slim\Http\Response;

class ProfileChecker
{

    /**
     * @var Server $auth
     */
    private $auth;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * ProfileChecker constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, Server $auth)
    {
        $this->entityManager = $entityManager;
        $this->auth = $auth;
    }

    public function checkEmailRoute(Request $request, Response $response)
    {
        $email = $request->getQueryParam('email', null);

        if (is_null($email)) {
            return $response->withJson('Email missing', 400);
        }

        $profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy(['email' => $email]);

        if (is_null($profile)) {
            return $response->withJson('Email not found', 404);
        }

        return $response->withJson('Email found', 200);
    }

    public function createProfile(Request $request, Response $response)
    {
        $email = $request->getParsedBodyParam('email', null);
        $password = $request->getParsedBodyParam('password', null);

        if (is_null($email) || is_null($password)) {
            return $response->withJson('Fields missing', 400);
        }

        $profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy(['email' => $email]);

        if (!is_null($profile)) {
            return $response->withJson('Profile exists', 404);
        }

        $profile = new UserProfile();
        $profile->setEmail($email);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $nearlyProfileAccountService = new NearlyProfileAccountService($this->entityManager);
        $nearlyProfileAccountService->createAccount($profile->getId(), ['password' => $password]);
        $oauth = new _oAuth2TokenController($this->auth, $this->entityManager);

        $code = $oauth->authCode($request, $response, $email, 'email', true);

        return $response->withJson($code, 200);
    }

    public function updatePassword(Request $request, Response $response)
    {
        $profileId = $request->getAttribute('profileId');
        $password = $request->getParsedBodyParam('password', null);
        if (is_null($password)) {
            return $response->withJson('NO_PASSWORD', 400);
        }
        $update = $this->entityManager->createQueryBuilder()
            ->update(UserAccount::class, 'u')
            ->set('u.password', ':password')
            ->where('u.id = :id')
            ->setParameter('password', hash('sha512', $password))
            ->setParameter('id', $profileId)
            ->getQuery()
            ->execute();

        if ($update === 1) {
            return $response->withJson('PASSWORD_UPDATED', 200);
        }

        return $response->withJson('FAILED_TO_UPDATE_PASSWORD', 400);
    }

    public function getMe(Request $request, Response $response)
    {

        $profileId = $request->getAttribute('profileId');
        /**
         * @var UserProfile $profile
         */

        $profile = $this->entityManager->getRepository(UserProfile::class)->find($profileId);

        if (is_null($profile)) {
            return $response->withJson('Profile not found', 404);
        }

        return $response->withJson($profile->jsonSerialize(), 200);
    }

    public function subscribeToNotifications(Request $request, Response $response)
    {

        $token = $request->getParsedBodyParam('token', null);
        $instanceId = $request->getParsedBodyParam('instanceId', null);
        $uid = strval($request->getAttribute('profileId'));

        if (is_null($token)) {
            return $response->withJson('REQUIRES_TOKEN', 400);
        }

        if (is_null($instanceId)) {
            return $response->withJson('REQUIRES_INSTANCE_ID', 400);
        }

        /**
         * @var FCMNotificationTokens $notificationToken
         */
        $notificationToken = $this->entityManager->getRepository(FCMNotificationTokens::class)->findOneBy([
            'uid' => $uid,
            'instanceId' => $instanceId,
        ]);

        if (is_null($notificationToken)) {
            $notificationToken = new FCMNotificationTokens($uid, $token, $instanceId);
        }
        $notificationToken->setToken($token);
        $this->entityManager->persist($notificationToken);
        $this->entityManager->flush();

        return $response->withJson(200);
    }

    /**
     * @return FCMNotificationTokens[] getNotificationTokens
     */
    public function getNotificationTokens(string $profileId): array
    {
        /**
         * @var FCMNotificationTokens[] $notificationToken
         */
        return $this->entityManager->getRepository(FCMNotificationTokens::class)->findAll([
            'uid' => $profileId,
        ]);
    }
}
