<?php
/**
 * Created by jamieaitken on 17/02/2018 at 16:31
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\Validations;

use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Models\Nearly\EmailDomainValid;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Slim\Http\Response;
use Slim\Http\Request;

class EmailValidator
{
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

    protected $freeDomains = [
        "aol.com",
        "att.net",
        "comcast.net",
        "facebook.com",
        "gmail.com",
        "gmx.com",
        "googlemail.com",
        "google.com",
        "hotmail.com",
        "hotmail.co.uk",
        "mac.com",
        "me.com",
        "mail.com",
        "msn.com",
        "live.com",
        "sbcglobal.net",
        "verizon.net",
        "yahoo.com",
        "yahoo.co.uk",
        "email.com",
        "fastmail.fm",
        "games.com",
        "gmx.net",
        "hush.com",
        "hushmail.com",
        "icloud.com",
        "iname.com",
        "inbox.com",
        "lavabit.com",
        "love.com",
        "outlook.com",
        "pobox.com",
        "protonmail.com",
        "rocketmail.com",
        "safe-mail.net",
        "wow.com",
        "ygm.com",
        "ymail.com",
        "zoho.com",
        "yandex.com",
        "bellsouth.net",
        "charter.net",
        "cox.net",
        "earthlink.net",
        "juno.com",
        "btinternet.com",
        "virginmedia.com",
        "blueyonder.co.uk",
        "freeserve.co.uk",
        "live.co.uk",
        "ntlworld.com",
        "o2.co.uk",
        "orange.net",
        "sky.com",
        "talktalk.co.uk",
        "tiscali.co.uk",
        "virgin.net",
        "wanadoo.co.uk",
        "bt.com",
        "sina.com",
        "qq.com",
        "naver.com",
        "hanmail.net",
        "daum.net",
        "nate.com",
        "yahoo.co.jp",
        "yahoo.co.kr",
        "yahoo.co.id",
        "yahoo.co.in",
        "yahoo.com.sg",
        "yahoo.com.ph",
        "hotmail.fr",
        "live.fr",
        "laposte.net",
        "yahoo.fr",
        "wanadoo.fr",
        "orange.fr",
        "gmx.fr",
        "sfr.fr",
        "neuf.fr",
        "free.fr",
        "gmx.de",
        "hotmail.de",
        "live.de",
        "online.de",
        "t-online.de",
        "web.de",
        "yahoo.de",
        "libero.it",
        "virgilio.it",
        "hotmail.it",
        "aol.it",
        "tiscali.it",
        "alice.it",
        "live.it",
        "yahoo.it",
        "email.it",
        "tin.it",
        "poste.it",
        "teletu.it",
        "mail.ru",
        "rambler.ru",
        "yandex.ru",
        "ya.ru",
        "list.ru",
        "hotmail.be",
        "live.be",
        "skynet.be",
        "voo.be",
        "tvcablenet.be",
        "telenet.be",
        "hotmail.com.ar",
        "live.com.ar",
        "yahoo.com.ar",
        "fibertel.com.ar",
        "speedy.com.ar",
        "arnet.com.ar",
        "yahoo.com.mx",
        "live.com.mx",
        "hotmail.es",
        "hotmail.com.mx",
        "prodigy.net.mx",
        "yahoo.com.br",
        "hotmail.com.br",
        "outlook.com.br",
        "uol.com.br",
        "bol.com.br",
        "terra.com.br",
        "ig.com.br",
        "itelefonica.com.br",
        "r7.com",
        "zipmail.com.br",
        "globo.com",
        "globomail.com",
        "oi.com.br"
    ];

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function emailValidatorRoute(Request $request, Response $response)
    {
        $params = $request->getQueryParams();

        if (!isset($params['email'])) {
            return $response->withJson(Http::status(400, 'EMAIL_MISSING'), 400);
        }

        $runChecks = $this->beginCheck($params['email'], $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($runChecks, $runChecks['status']);
    }

    private function beginCheck(string $email, string $serial)
    {

        $validator = new \Egulias\EmailValidator\EmailValidator();
        $valid     = $validator->isValid($email, new RFCValidation());

        if (!$valid) {
            return Http::status(400, 'EMAIL_NOT_GENUINE');
        }

        $positionOfDomain = strpos($email, '@') + 1;
        $domain           = substr($email, $positionOfDomain);

        $isValidDomain = $this->em->createQueryBuilder()
            ->select('u.isValid')
            ->from(EmailDomainValid::class, 'u')
            ->where('u.domainName = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getArrayResult();

        if (empty($isValidDomain)) {
            $externalCheck  = $validator->isValid($email, new DNSCheckValidation());
            $newDomainCheck = new EmailDomainValid($domain, $externalCheck);
            $this->em->persist($newDomainCheck);
            $this->em->flush();

            if (!$externalCheck) {
                return Http::status(400, 'EMAIL_NOT_GENUINE');
            }
        } elseif (!$isValidDomain[0]['isValid']) {
            return Http::status(400, 'EMAIL_NOT_GENUINE');
        }

        $newNearlyOther = new LocationOtherController($this->em);

        $otherId = $newNearlyOther->getOtherIdLocationBySerial($serial);
        $other   = $newNearlyOther->getNearlyOther($serial, $otherId)['message'];

        if ($other['allowSpamEmails'] === false) {
            if (in_array($domain, $this->domainBlacklist)) {
                return Http::status(400, 'EMAIL_DOMAIN_IS_A_TEMPORARY_ONE');
            }
        }

        if ($other['onlyBusinessEmails'] === true) {
            if (in_array($domain, $this->freeDomains) || in_array($domain, $this->domainBlacklist)) {
                return Http::status(400, 'EMAIL_DOMAIN_IS_NOT_BUSINESS_ONE');
            }
        }

        return Http::status(200, 'EMAIL_VALID');
    }

    public function connectCheck(string $email)
    {
        $validator           = new \Egulias\EmailValidator\EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);

        $valid = $validator->isValid($email, $multipleValidations);
        if (!$valid) {
            return false;
        }

        return true;
    }
}