<?php
/**
 * Created by jamieaitken on 28/06/2018 at 11:30
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\Mail\_MailController;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\OauthUser;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class GDPRNotifierWorker
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {

        $endUserDetails = $this->em->createQueryBuilder()
                              ->select('u.email')
                              ->from(UserProfile::class, 'u')
                              ->where('u.id = :profileId')
                              ->setParameter('profileId', $body['profileId'])
                              ->getQuery()
                              ->getArrayResult()[0];

        if (is_null($endUserDetails['email']) || empty($endUserDetails['email'])) {
            return false;
        }

        $getAdminsEmail = $this->em->createQueryBuilder()
                              ->select('u.email, u.first, u.last, na.memberKey')
                              ->from(OauthUser::class, 'u')
                              ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'u.uid = na.admin')
                              ->where('na.serial = :serial')
                              ->setParameter('serial', $body['serial'])
                              ->getQuery()
                              ->getArrayResult()[0];

        $getModeratorsEmail = $this->em->createQueryBuilder()
            ->select('u.email, u.first, u.last')
            ->from(OauthUser::class, 'u')
            ->leftJoin(NetworkAccessMembers::class, 'na', 'WITH', 'u.uid = na.memberId')
            ->where('u.role = :role')
            ->andWhere('na.memberKey = :memberKey')
            ->setParameter('role', 3)
            ->setParameter('memberKey', $getAdminsEmail['memberKey'])
            ->getQuery()
            ->getArrayResult();

        $mail = new _MailController($this->em);
        foreach ($getModeratorsEmail as $mod) {
            $mail->send([
                [
                    'to'   => $mod['email'],
                    'name' => $mod['first'] . ' ' . $mod['last']
                ]
            ],
                [
                    'email'  => $endUserDetails['email'],
                    'id'     => $body['profileId'],
                    'serial' => $body['serial']
                ],
                'GDPRNotify',
                'GDPR Compliance: Remove Person From Mailing Lists');
        }

        $mail->send([
            [
                'to'   => $getAdminsEmail['email'],
                'name' => $getAdminsEmail['first'] . ' ' . $getAdminsEmail['last']
            ]
        ],
            [
                'email' => $endUserDetails['email'],
                'id'    => $body['profileId']
            ],
            'GDPRNotify',
            'GDPR Compliance: Remove Person From Mailing Lists'
        );

        return true;
    }
}