<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 17/03/2017
 * Time: 10:43
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _MikrotikDeviceController extends _MikrotikConfigController
{

    public $command = '';

    public function __construct(EntityManager $em)
    {
        $this->command = '';
        parent::__construct($em);
    }

    public function addWhitelist($mac, $id, $serial)
    {
        $command = '/ip hotspot ip-binding add comment="' . $id . '" mac-address=' . $mac . ' type=bypassed' . PHP_EOL;
        $this->buildConfig($command, $serial);
    }

    public function deleteWhitelist($id, $serial)
    {
        $command = '/ip hotspot ip-binding remove [find comment="' . $id . '"]' . PHP_EOL;
        $this->buildConfig($command, $serial);
    }

    public function addDevice($id, $ip, $mac, $port, $serial)
    {

        $dst_port = $this->portMaker($ip);

        $command = '/ip hotspot ip-binding add comment="' . $id . '" to-address=' . $ip . ' mac-address=' . $mac . ' type=bypassed' . PHP_EOL;
        $command .= '/ip dhcp-server lease add comment="' . $id . '" address=' . $ip . ' mac-address=' . $mac . PHP_EOL;
        $command .= '/ip firewall nat add comment="' . $id . '" action=dst-nat chain=dstnat dst-port=' . $dst_port . ' protocol=tcp to-addresses=\ ' . $ip . ' to-ports=' . $port . PHP_EOL;
        $command .= '/tool netwatch add comment="' . $id . '" down-script="/tool fetch url=\\"https://api.stampede.ai/devices/checkin/' . $id . '/0\\"" host=' . $ip . ' up-script="/tool fetch url=\\"https://api.stampede.ai/devices/checkin/' . $id . '/1\\""';

        $this->buildConfig($command, $serial);
    }

    public function deleteDevice($id, $serial)
    {
        $command = '/ip hotspot ip-binding remove [find comment=' . $id . ']' . PHP_EOL;
        $command .= '/tool netwatch remove [find comment=' . $id . ']' . PHP_EOL;
        $command .= '/ip firewall nat remove [find comment=' . $id . ']' . PHP_EOL;
        $command .= '/ip dhcp-server lease remove [find comment=' . $id . ']' . PHP_EOL;

        $this->buildConfig($command, $serial);
    }

    public function removeAuth($mac, $serial)
    {
        $command = '/ip hotspot host remove [ find mac-address=' . $mac . ' ]';
        $this->buildConfig($command, $serial);
    }

    public function removeAuthCommand($mac)
    {
        return '/ip hotspot host remove [ find mac-address=' . $mac . ' ]';
    }

    public function setDNS($rules = [], $serial = '')
    {
        $rules   = implode(',', $rules);
        $command = '/ip dns set allow-remote-requests=yes servers=' . $rules;

        $this->buildConfig($command, $serial);
    }

    public function portMaker($ip)
    {

        $last_digit = explode('.', $ip);

        if (end($last_digit) <= 9) {
            $dst_port = 800 . end($last_digit);
        } else {
            if (end($last_digit) <= 99) {
                $dst_port = 80 . end($last_digit);
            } else {
                $dst_port = 8 . end($last_digit);
            }
        }

        return ($dst_port);
    }
}
