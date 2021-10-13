<?php

/**
 * Created by jamieaitken on 10/01/2019 at 17:12
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Utils\Validators;

use App\Models\BouncedEmails;
use App\Models\Nearly\EmailDomainValid;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class Email
{
    protected $em;

    private $domainBlacklist = [
        'mvrht.net',
        '20email.eu',
        '33mail.com',
        'e4ward.com',
        'armyspy.com',
        'teleworm.us',
        'cuvox.de',
        'dayrep.com',
        'einrot.com',
        'fleckens.hu',
        'gustr.com',
        'jourrapide.com',
        'rhyta.com',
        'superito.com',
        'filzmail.com',
        'sharklasers.com',
        'guerrillamail.info',
        'grr.la',
        'guerrillamail.biz',
        'guerrillamail.com',
        'guerrillamail.de',
        'guerrillamail.net',
        'guerrillamail.org',
        'guerrillamailblock.com',
        'pokemail.net',
        'spam4.me',
        'incognitomail.org',
        'mailcatch.com',
        'mailinator.com',
        'mailnesia.com',
        'mt2015.com',
        'justnowmail.com',
        'spamgourmet.com',
        'youzend.net',
        'postix.info',
        'mail4-us.org',
        '0box.eu',
        'contbay.com',
        'damnthespam.com',
        'kurzepost.de',
        'objectmail.com',
        'proxymail.eu',
        'rcpt.at',
        'trash-mail.at',
        'trashmail.at',
        'trashmail.com',
        'trashmail.io',
        'trashmail.me',
        'trashmail.net',
        'wegwerfmail.de',
        'wegwerfmail.net',
        'wegwerfmail.org',
        'trashmail.ws',
        'yopmail.fr',
        'yopmail.net',
        'cool.fr.nf',
        'jetable.fr.nf',
        'nospam.ze.tc',
        'nomail.xl.cx',
        'mega.zik.dj',
        'speed.1s.fr',
        'courriel.fr.nf',
        'moncourrier.fr.nf',
        'monemail.fr.nf',
        'monmail.fr.nf'
    ];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function isValidRoute(Request $request, Response $response)
    {

        $send = $this->isValid($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function isValidGetRoute(Request $request, Response $response)
    {


        $email = $request->getQueryParam('email', null);
        if (is_null($email)) {
            $send = Http::status(200);
        } else {
            $send = $this->isValid($email);
            $this->em->clear();
        }

        return $response->withJson($send, $send['status']);
    }

    public function isValid(?array $body)
    {
        if (is_null($body)) {
            return Http::status(400, 'BODY_MISSING');
        }
        if (!isset($body['email'])) {
            return Http::status(400, 'EMAIL_MISSING');
        }
        $email = $body['email'];

        $validator     = new \Egulias\EmailValidator\EmailValidator();
        $isValidFormat = $validator->isValid($email, new RFCValidation());

        if (!$isValidFormat) {
            return Http::status(400, 'EMAIL_NOT_GENUINE');
        }

        $bounced = $this->em->getRepository(BouncedEmails::class)->find($email);
        if (!is_null($bounced)) {
            return Http::status(400, 'EMAIL_PREVIOUSLY_BOUNCED');
        }

        $beginningOfDomain = strpos($email, '@') + 1;
        $domain            = substr($email, $beginningOfDomain);

        $hasBeenPreviouslyEntered = $this->em->createQueryBuilder()
            ->select('u.isValid')
            ->from(EmailDomainValid::class, 'u')
            ->where('u.domainName = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getArrayResult();

        if (empty($hasBeenPreviouslyEntered)) {
            $doesDomainHaveMXRecord = $validator->isValid($email, new DNSCheckValidation());
            $newDomainCheck         = new EmailDomainValid($domain, $doesDomainHaveMXRecord);
            $this->em->persist($newDomainCheck);
            $this->em->flush();

            if (!$doesDomainHaveMXRecord) {
                return Http::status(400, 'EMAIL_NOT_GENUINE');
            }
        } elseif (!$hasBeenPreviouslyEntered[0]['isValid']) {
            return Http::status(400, 'EMAIL_NOT_GENUINE');
        }


        if (in_array($domain, $this->domainBlacklist)) {
            return Http::status(400, 'EMAIL_DOMAIN_IS_A_TEMPORARY_ONE');
        }


        return Http::status(200);
    }
}
