<?php

/**
 * Created by jamieaitken on 14/02/2018 at 12:04
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Radius;

use App\Package\Nearly\NearlyInput;
use Doctrine\ORM\EntityManager;

class RadiusNearlySettings
{
    protected $em;
    protected $secret = "sharedpass";

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function encode_password(string $plain, string $challenge, string $secret)
    {
        if ((strlen($challenge) % 2) != 0 ||
            strlen($challenge) == 0
        ) {
            return false;
        }

        $hexchall = hex2bin($challenge);
        if ($hexchall === false) {
            return false;
        }

        if (strlen($secret) > 0) {
            $crypt_secret = md5($hexchall . $secret, true);
            $len_secret   = 16;
        } else {
            $crypt_secret = $hexchall;
            $len_secret   = strlen($hexchall);
        }

        /* simulate C style \0 terminated string */
        $plain   .= "\x00";
        $crypted = '';
        for ($i = 0; $i < strlen($plain); $i++) {
            $crypted .= $plain[$i] ^ $crypt_secret[$i % $len_secret];
        }

        $extra_bytes = 0; //rand(0, 16);
        for ($i = 0; $i < $extra_bytes; $i++) {
            $crypted .= chr(rand(0, 255));
        }

        return bin2hex($crypted);
    }

    public function radiusLink(NearlyInput $input)
    {
        $ip = $input->getAp();
        $port = $input->getPort();
        $serial = $input->getSerial();
        $secret = $this->setSecret($input->getSerial());
        $encoded_password = $this->encode_password(
            $input->getProfileId(),
            $input->getChallenge(),
            $secret
        );


        return "http://$ip:$port/logon?" .
            "username=" . urlencode($input->getProfileId() . $input->getSerial()) .
            "&password=" . urlencode($encoded_password) .
            "&redir=" . urlencode("https://nearly.online/landing/$serial");
    }

    public function radiusSimpleLink(string $ip, string $port, string $profileId, string $password)
    {
        return "http://$ip:$port/logon?" .
            "username=" . urlencode($password) .
            "&password=" . urlencode($profileId);
    }

    public function setSecret(string $serial)
    {
        $secret       = new _RadiusController($this->em);
        $get =  $secret->getSecret($serial);
        if ($get['status'] === 200) {
            $this->secret = $get['message'];
        }
        return $get['message'];
    }
}
