<?php

/**
 * Created by jamieaitken on 14/02/2018 at 11:36
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\OpenMesh;

use App\Controllers\Integrations\Radius\_RadiusController;
use App\Controllers\Integrations\Radius\RadiusNearlySettings;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class OpenMeshNearlySettings extends RadiusNearlySettings
{
    protected $em;
    protected $mail;
    public $secret = "sharedpass";
    public $password = "userpassword";
    public $defaultPassword = "userpassword";
    public $mac = false;
    public $type = "";
    public $ip = "";
    public $serial = "";
    public $landing = 'https://google.com';

    public $response = [
        'CODE'        => 'REJECT',
        'RA'          => '0123456789abcdef0123456789abcdef',
        'BLOCKED_MSG' => 'Rejected! This doesnt look like a valid request',
    ];

    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
        parent::__construct($this->em);
    }

    public function getLandingAndSecret(string $serial)
    {
        $secret        = new _RadiusController($this->em);
        $this->secret  = $secret->getSecret($serial)['message'];
        return Http::status(200);
    }

    public function parseAuth($get, $serial)
    {

        /* copy request authenticator */
        if (array_key_exists(
            'ra',
            $get
        ) && strlen($get['ra']) == 32 && ($ra = hex2bin($get['ra'])) !== false && strlen($ra) == 16) {
            $this->response['RA'] = $get['ra'];
        }

        $this->password = false;
        if (array_key_exists('username', $get) && array_key_exists('password', $get)) {
            $this->password = $this->decode_password($this->response, $get['password'], $this->secret);
        }

        /* store mac when available */
        if (array_key_exists('mac', $get)) {
            $this->mac = $get["mac"];
        }

        if (array_key_exists('type', $get)) {
            $this->type = $get['type'];
        }

        if (array_key_exists('ipv4', $get)) {
            $this->ip = $get['ipv4'];
        }

        $this->serial = $serial;
    }

    function calculate_new_ra(&$dict, $secret)
    {
        if (!array_key_exists('CODE', $dict)) {
            return;
        }
        $code = $dict['CODE'];
        if (!array_key_exists('RA', $dict)) {
            return;
        }
        if (strlen($dict['RA']) != 32) {
            return;
        }
        $ra = hex2bin($dict['RA']);
        if ($ra === false) {
            return;
        }

        $this->response['RA'] = hash('md5', $code . $ra . $secret);
    }

    public function decode_password($dict, $encoded, $secret)
    {
        if (!array_key_exists('RA', $dict)) {
            return false;
        }
        if (strlen($dict['RA']) != 32) {
            return false;
        }
        $ra = hex2bin($dict['RA']);
        if ($ra === false) {
            return false;
        }
        if ((strlen($encoded) % 32) != 0) {
            return false;
        }
        $bincoded    = hex2bin($encoded);
        $password    = "";
        $last_result = $ra;
        for ($i = 0; $i < strlen($bincoded); $i += 16) {
            $key = hash('md5', $secret . $last_result, true);
            for ($j = 0; $j < 16; $j++) {
                $password .= $key[$j] ^ $bincoded[$i + $j];
            }
            $last_result = substr($bincoded, $i, 16);
        }
        $j = 0;
        for ($i = strlen($password); $i > 0; $i--) {
            if ($password[$i - 1] != "\x00") {
                break;
            } else {
                $j++;
            }
        }
        if ($j > 0) {
            $password = substr($password, 0, strlen($password) - $j);
        }

        return $password;
    }

    public function link(string $ip, string $port, string $mac, string $challenge)
    {
        $encoded_password = $this->encode_password(
            $this->defaultPassword,
            $challenge,
            $this->secret
        );

        $redirect_url = "http://$ip:$port/logon?" .
            "username=" . urlencode($mac) .
            "&password=" . urlencode($encoded_password);

        return $redirect_url;
    }
}
