<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 19/04/2017
 * Time: 15:40
 */

namespace App\Controllers\Integrations\UniFi;


use App\Models\Unifi;
use App\Models\UnifiController;
use Doctrine\ORM\EntityManager;

/**
 * Class _UniFiConnectedController
 * @package App\Controllers\Integrations\UniFi
 */
class _UniFiConnectedController extends _UniFiSettingsController
{
    public $clients = [];

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function getConnected($serial)
    {
        $settings = $this->settings($serial);
        $settings = $settings['message'];
        if (is_null($settings['unifiId'])) {
            return $this->clients;
        }
        $unifi = new _UniFi($settings['username'], $settings['password'], $settings['hostname'],
            $settings['unifiId']);

        if (!$unifi->login) {
            return [];
        }

        $this->clients = $unifi->clients();

        return $this->clients;
    }

    public function mergeClients($connected)
    {
        $connectedResponse = [];

        foreach ($this->clients as $unifiClient) {
            if ($unifiClient->is_guest === true) {
                if (!property_exists($unifiClient, 'hostname')) {
                    $unifiClient->hostname = 'unknown';
                }
                if (!property_exists($unifiClient, 'ip')) {
                    $unifiClient->ip = 'unknown';
                }

                $payload = [
                    'hostname'   => $unifiClient->hostname,
                    'idletime'   => $unifiClient->idletime,
                    'authorized' => $unifiClient->authorized,
                    'ip'         => $unifiClient->ip,
                    'mac'        => strtoupper($unifiClient->mac),
                    'signal'     => $unifiClient->signal,
                    'rssi'       => $unifiClient->rssi,
                    'download'   => 0,
                    'upload'     => 0
                ];

                foreach ($connected as $client) {
                    if (strtoupper($client['mac']) === $payload['mac'] || strtoupper(hash('sha512',
                            $client['mac'])) === hash('sha512', $payload['mac'])) {
                        $payload = array_merge($payload, $client);
                    }
                }

                $connectedResponse[] = $payload;
            }
        }

        return $connectedResponse;
    }
}
