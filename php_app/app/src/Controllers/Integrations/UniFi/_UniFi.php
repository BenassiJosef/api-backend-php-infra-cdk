<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 19/04/2017
 * Time: 16:45
 */

namespace App\Controllers\Integrations\UniFi;

use App\Controllers\Integrations\WalledGardenWhitelist;
use App\Utils\unifiapi;
use Curl\Curl;

class _UniFi
{

    private $username = '';
    private $password = '';
    private $hostname = '';
    private $site = '';
    public $auth = unifiapi::class;
    public $login = false;

    /**
     * _UniFi constructor.
     * @param string $username
     * @param string $password
     * @param string $hostname
     * @param string $site
     */

    function __construct($username = '', $password = '', $hostname = '', $site = '')
    {

        $this->username = $username;
        $this->password = $password;
        $this->hostname = $hostname;
        $this->site     = $site;

        $auth        = new unifiapi($username, $password, 'https://' . $hostname, $site);
        $this->auth  = $auth;
        $this->login = $this->auth->login();
    }

    /**
     * @return array|bool
     *  * [
     * '_id' => '58f77737491afd201fdf51f0',
     * '_is_guest_by_uap' => true,
     * '_last_seen_by_uap' => 1492620833,
     * '_uptime_by_uap' => 7982,
     * 'ap_mac' => '44:d9:e7:02:f9:ec',
     * 'assoc_time' => 1492612850,
     * 'authorized' => true,
     * 'bssid' => '46:d9:e7:04:f9:ec',
     * 'bytes-r' => 0,
     * 'ccq' => 333,
     * 'channel' => 36,
     * 'essid' => 'FREESPOT',
     * 'first_seen' => 1492612919,
     * 'guest_id' => '58f777ea491afd201fdf5307',
     * 'hostname' => 'Shauns-iPad',
     * 'idletime' => 5,
     * 'ip' => '192.168.20.157',
     * 'is_guest' => true,
     * 'is_wired' => false,
     * 'last_seen' => 1492620833,
     * 'latest_assoc_time' => 1492612853,
     * 'mac' => 'cc:c7:60:67:d3:c8',
     * 'noise' => -105,
     * 'oui' => 'Apple',
     * 'powersave_enabled' => false,
     * 'qos_policy_applied' => true,
     * 'radio' => 'na',
     * 'radio_proto' => 'na',
     * 'rssi' => 40,
     * 'rx_bytes' => 15400515,
     * 'rx_bytes-r' => 0,
     * 'rx_packets' => 124561,
     * 'rx_rate' => 300000,
     * 'signal' => -65,
     * 'site_id' => '570b60601faa47d4c79f7256',
     * 'tx_bytes' => 201635757,
     * 'tx_bytes-r' => 0,
     * 'tx_packets' => 163020,
     * 'tx_power' => 40,
     * 'tx_rate' => 300000,
     * 'uptime' => 7983,
     * 'user_id' => '58f77737491afd201fdf51f0',
     * 'vlan' => 0,
     * ]
     */

    public function clients()
    {
        return $this->auth->list_clients();
    }

    /**
     * @param string $mac
     * @param int $timeout
     * @param string $ap
     * @return bool
     */

    public function authClient($mac = '', $timeout = 0, $ap = '')
    {
        $mac = strtolower($mac);
        $ap  = strtolower($ap);

        return $this->auth->authorize_guest($mac, $timeout, null, null, null, $ap);
    }

    /**
     * @param $mac
     * @return bool
     */

    public function unauthClient($mac)
    {
        return $this->auth->unauthorize_guest($mac);
    }

    /**
     * @return bool
     */
    public function setGuestPolicy()
    {
        $ip       = '52.208.193.150';
        $hostname = 'nearly.online';

        $payload = [
            'portal_enabled'    => true,
            'portal_use_hostname' => true,
            'portal_hostname'     => $hostname,
            'custom_ip'           => $ip,
            'site_id'             => $this->site,
            'auth'                => 'custom'
        ];
        $newWhiteListController = new WalledGardenWhitelist();
        $whitelist = $newWhiteListController->getCompleteList();

        $count = 0;
        foreach ($whitelist as $wl) {
            $count++;
            $key             = "allowed_subnet_" . $count;
            $payload[$key] = $wl;
        }

        return $this->auth->set_guestlogin_settings_base($payload);
    }

    public function listSites()
    {
        return $this->auth->list_sites();
    }

    public function listAps()
    {
        return $this->auth->list_devices();
    }

    public function listSsid()
    {
        return $this->auth->list_wlanconf();
    }

    public function setSite($siteId)
    {
        $this->auth->site = $siteId;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;
    }

    public function version()
    {
        $stats = $this->auth->stat_sysinfo();

        return $stats[0]->version;
    }

    public function hasIOSFixEnabled()
    {
        $authType = 'custom';

        $hostname = substr($this->hostname, 0, strpos($this->hostname, ':'));

        $requestUnifi = new Curl();
        $requestUnifi->setConnectTimeout(1);
        $requestUnifi->get('http://' . $hostname . ':8880' . '/guest/s/' . $this->site);
        if (strpos($requestUnifi->response, 'nearly.online') !== false) {
            $authType = 'hotspot';
        }

        return $authType;
    }
}
