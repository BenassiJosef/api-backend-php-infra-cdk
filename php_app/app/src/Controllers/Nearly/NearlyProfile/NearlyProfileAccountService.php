<?php
/**
 * Created by jamieaitken on 04/05/2018 at 09:18
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;

use App\Controllers\Auth\_PasswordController;
use App\Models\User\UserAccount;
use App\Models\UserProfile;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyProfileAccountService
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {
        $send = $this->createAccount($request->getAttribute('nearlyUser')['profileId'], $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function hasAccountRoute(Request $request, Response $response)
    {

        $send = $this->hasAccount($request->getAttribute('nearlyUser')['profileId']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function resetPasswordRoute(Request $request, Response $response)
    {
        $send = $this->resetPassword($request->getAttribute('nearlyUser')['profileId']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePasswordRoute(Request $request, Response $response)
    {
        $send = $this->updatePassword($request->getAttribute('nearlyUser')['profileId'], $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createAccount(string $id, array $body)
    {

        $doesExist = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(UserAccount::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        if (!empty($doesExist)) {
            return Http::status(409, 'ACCOUNT_EXISTS_FOR_USER');
        }

        $createAccount = new UserAccount($id, $this->createOrUpdatePassword($body['password']));

        $this->em->persist($createAccount);

        $this->em->flush();

        return Http::status(200);
    }

    public function createOrUpdatePassword(string $password)
    {
        return hash('sha512', $password);
    }

    public function hasAccount(string $id)
    {
        $query = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(UserAccount::class, 'u')
            ->where('u.id = :i')
            ->setParameter('i', $id)
            ->getQuery()
            ->getArrayResult();

        if (empty($query)) {
            return Http::status(204);
        }

        return Http::status(200);
    }

    public function updatePassword(string $id, array $body)
    {
        if (!isset($body['oldPassword'], $body['newPassword'])) {
            return Http::status(400, 'MISSING_PARAMS');
        }

        $verifyPassword = $this->em->createQueryBuilder()
            ->select('u.password')
            ->from(UserAccount::class, 'u')
            ->where('u.id = :id')
            ->andWhere('u.password = :pass')
            ->setParameter('id', $id)
            ->setParameter('pass', hash('sha512', $body['oldPassword']))
            ->getQuery()
            ->getArrayResult();

        if (empty($verifyPassword)) {
            return Http::status(409, 'WRONG_PASSWORD');
        }

        $update = $this->em->createQueryBuilder()
            ->update(UserAccount::class, 'u')
            ->set('u.password', ':password')
            ->where('u.id = :id')
            ->setParameter('password', hash('sha512', $body['newPassword']))
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();

        if ($update === 1) {
            return Http::status(200, 'PASSWORD_UPDATED');
        }

        return Http::status(400, 'FAILED_TO_UPDATE_PASSWORD');
    }

    public function resetPassword(string $id)
    {
        $hasProfile = $this->em->getRepository(UserAccount::class)->findOneBy([
            'id' => $id
        ]);

        if (is_null($hasProfile)) {
            return Http::status(404, 'NO_PREVIOUS_ACCOUNT');
        }

        $getEmail = $this->em->getRepository(UserProfile::class)->findOneBy([
            'id' => $hasProfile->id
        ]);

        $passwordService = new _PasswordController($this->em);
        $passwordService->forgotPassword($getEmail->email, null, 'nearly');

        return Http::status(200, 'EMAIL_SENT');
    }
}