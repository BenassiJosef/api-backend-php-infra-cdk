<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 31/01/2017
 * Time: 10:51
 */

namespace App\Controllers\Auth;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileAccountService;
use App\Models\OauthUser;
use App\Models\PasswordReset;
use App\Models\User\UserAccount;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Slim\Http\Response;
use Slim\Http\Request;

class _PasswordController
{
    protected $em;

    protected $mail;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }

    public function forgotPasswordRoute(Request $request, Response $response)
    {
        $body  = $request->getQueryParams();
        $email = $body['email'];

        $clientId = isset($body['client_id']) ? $body['client_id'] : 'connect';

        $admin = null;

        if ($clientId === 'connect' || $clientId === 'stampede.ai.connect' || $clientId === 'insight_app_android' || $clientId === 'insight_app_ios') {
            $admin = $this->em->getRepository(OauthUser::class)->findOneBy([
                'email' => $email,
                'deleted' => false
            ]);
            $admin = $admin->admin;
        }

        $send = $this->forgotPassword($email, $admin, $clientId);

        $mp = new _Mixpanel();
        $mp->track('forgot_password_send', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function masterChangeRoute(Request $request, Response $response)
    {

        $body     = $request->getParsedBody();
        $loggedIn = $request->getAttribute('accessUser');
        $send     = $this->masterChange($loggedIn['uid'], $body['password']);

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('master_password_update', ['uid' => $loggedIn['uid']]);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function changePasswordRoute(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $body = $request->getParsedBody();
        $send = $this->updatePassword($body['oldPassword'], $body['newPassword'], $user['uid']);

        $mp = new _Mixpanel();
        $mp->identify($user['uid'])->track('password_update', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePasswordFromTokenRoute(Request $request, Response $response)
    {
        $body  = $request->getParsedBody();
        $token = $request->getAttribute('token');
        $send  = $this->updatePasswordFromToken($body['password'], $token);

        return $response->withJson($send, $send['status']);
    }

    public function masterChange(string $memberId, string $password)
    {
        $update = $this->updatePassword('', $password, $memberId);

        $this->em->clear();

        if ($update['status'] === 200) {
            return Http::status(200, 'PASSWORD_UPDATED');
        }

        return Http::status(404, 'FAILED_TO_UPDATE_PASSWORD');
    }

    public function resetPasswordRoute(Request $request, Response $response)
    {
        $token = $request->getAttribute('token');
        $send  = $this->verifyToken($token);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function forgotPassword(string $email, $admin = null, string $service)
    {

        $dateTime            = new \DateTime();
        $newReset            = new PasswordReset;
        $newReset->token     = Uuid::uuid1();
        $newReset->email     = $email;
        $newReset->service   = $service;
        $newReset->createdAt = $dateTime;

        $this->em->persist($newReset);
        $this->em->flush();

        $this->mail->send([
            [
                'to'   => $email,
                'name' => $email
            ]
        ], [
            'message' => 'You have requested to reset your password',
            'link'    => 'reset/' . $newReset->token,
            'admin'   => $admin
        ],
            'ResetPassword',
            'Requested Password Reset'
        );


        return Http::status(200, 'EMAIL_SENT_PLEASE_VERIFY_TOKEN');
    }

    public function verifyToken(string $token)
    {
        $date = new \DateTime();

        $select = $this->em->createQueryBuilder()
            ->select('u')
            ->from(PasswordReset::class, 'u')
            ->where('u.token = :token')
            ->andWhere('u.createdAt > :past')
            ->andWhere('u.valid = :tokenValid')
            ->setParameter('token', $token)
            ->setParameter('past', $date->modify('-30 minutes'))
            ->setParameter('tokenValid', 1)
            ->getQuery()
            ->getArrayResult();

        if (!empty($select)) {
            return Http::status(200, $select[0]);
        }

        return Http::status(404, 'TOKEN_EITHER_EXPIRED_OR_DOES_NOT_EXIST');
    }

    public function updatePasswordFromToken(string $password, string $token)
    {

        $passwordReset = $this->verifyToken($token);

        if ($passwordReset['status'] !== 200) {
            return $passwordReset;
        }

        if ($passwordReset['message']['service'] === 'connect' || $passwordReset['message']['service'] === 'stampede.ai.connect') {
            $update = $this->em->createQueryBuilder()
                ->update(OauthUser::class, 'u')
                ->set('u.password', ':password')
                ->where('u.email = :email')
                ->setParameter('email', $passwordReset['message']['email'])
                ->setParameter('password', sha1($password))
                ->getQuery()
                ->execute();
        } elseif ($passwordReset['message']['service'] === 'nearly') {

            $getProfileIdViaEmail = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(UserProfile::class, 'u')
                ->where('u.email = :email')
                ->setParameter('email', $passwordReset['message']['email'])
                ->getQuery()
                ->getArrayResult();

            $newNearlyAccountService = new NearlyProfileAccountService($this->em);

            $update = $this->em->createQueryBuilder()
                ->update(UserAccount::class, 'u')
                ->set('u.password', ':password')
                ->where('u.id = :id')
                ->setParameter('id', $getProfileIdViaEmail[0]['id'])
                ->setParameter('password', $newNearlyAccountService->createOrUpdatePassword($password))
                ->getQuery()
                ->execute();
        }

        if ($update === 1) {
            $reset        = $this->em->getRepository(PasswordReset::class)->find($passwordReset['message']['id']);
            $reset->valid = false;

            $this->em->persist($reset);
            $this->em->flush();

            return Http::status(200, 'UPDATED_PASSWORD');
        }

        return Http::status(400, 'FAILED_TO_UPDATE_PASSWORD');
    }

    public function updatePassword(string $oldPassword = '', string $password, string $id)
    {
        if (!empty($oldPassword)) {
            $passwordCheck = $this->em->createQueryBuilder()
                ->select('p.password')
                ->from(OauthUser::class, 'p')
                ->where('p.password = :password')
                ->andWhere('p.uid = :id')
                ->setParameter('password', sha1($oldPassword))
                ->setParameter('id', $id)
                ->getQuery()
                ->getArrayResult();

            if (empty($passwordCheck)) {
                return Http::status(409, 'WRONG_PASSWORD');
            }
        }

        $update = $this->em->createQueryBuilder()
            ->update(OauthUser::class, 'u')
            ->set('u.password', ':password')
            ->where('u.uid = :id')
            ->setParameter('password', sha1($password))
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();

        if (!empty($update)) {
            return Http::status(200, 'PASSWORD_UPDATED');
        }

        return Http::status(400, 'PASSWORD_FAILED_TO_UPDATE');
    }
}
