<?php
/**
 * Created by jamieaitken on 08/10/2018 at 15:36
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Members;

use App\Controllers\Nearly\Validations\EmailValidator;
use App\Models\OauthUser;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class MemberValidationController implements \App\Package\Member\EmailValidator
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function isValidRoute(Request $request, Response $response)
    {

        $send = $this->isValid($request->getParsedBody());

        return $response->withJson($send, $send['status']);
    }

    public function isValid(array $body)
    {
        if (!$this->validateEmail($body['email'])) {
            return Http::status(409);
        }


        return Http::status(200);
    }

    public function validateEmail(string $email)
    {
        $emailCheck = $this->em->createQueryBuilder()
            ->select('u.email')
            ->from(OauthUser::class, 'u')
            ->where('u.email = :email')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('email', $email)
            ->setParameter('deleted', 0)
            ->getQuery()
            ->getArrayResult();

        if (!empty($emailCheck)) {
            return false;
        }

        $emailValidator = new EmailValidator($this->em);

        return $emailValidator->connectCheck($email);

    }
}