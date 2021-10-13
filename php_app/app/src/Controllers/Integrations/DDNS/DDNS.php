<?php

namespace App\Controllers\Integrations\DDNS;

use App\Utils\CacheEngine;
use Curl\Curl;

class DDNS
{

    private $secret = 'EF5C4D144D1FCAB2';
    private $hostname = 'simpleddns.io';
    private $api = 'ddns.blackbx.io';
    private $hostnameToUpdate = '';
    private $ip = '';
    private $hash = '';
    protected $cache;

    public function __construct(string $ip, string $serial)
    {
        $this->hostnameToUpdate = strtolower($serial) . '.' . $this->hostname;
        $this->ip               = $ip;
        $this->cache            = new CacheEngine(getenv('NEARLY_REDIS'));
        $this->hash             = hash('sha256', $this->ip . $this->hostnameToUpdate . '.' . $this->secret);
    }

    public function create()
    {
        $request = new Curl();

        $request->setHeader('x-api-key', 'ShO0V5WzK43TawOinsvYg9P67ApzhtZi7BiAb8Zn');
        $request->setHeader('Content-Type', 'application/json');

        $request->post('https://' . $this->api . '/provision', [
            'hostname' => $this->hostnameToUpdate . '.'
        ]);

        if (!$request->error) {
            $this->cache->delete($this->hostnameToUpdate);
        }

        return $this;
    }

    public function save()
    {

        $cache = $this->cache->fetch($this->hostnameToUpdate);

        if (!is_bool($cache)) {
            if ($cache['ip'] === $this->ip) {
                return $this;
            }
        }

        $request = new Curl();
        $request->setHeader('x-api-key', 'ShO0V5WzK43TawOinsvYg9P67ApzhtZi7BiAb8Zn');
        $request->setHeader('Content-Type', 'application/json');

        $request->get('https://' . $this->api, [
            'mode'       => 'set',
            'hostname'   => $this->hostnameToUpdate,
            'hash'       => $this->hash,
            'internalIp' => $this->ip
        ]);

        if (!$request->error) {
            $this->cache->save($this->hostnameToUpdate, [
                'ip' => $this->ip
            ]);
        }

        return $this;
        //  return ['hash' => $this->hash, 'string' => $this->ip . $this->hostnameToUpdate . '.' . $this->secret, 'ip' => $this->ip, 'hostname' => $this->hostnameToUpdate, 'secret' => $this->secret, 'response' => $request->response];
    }

    public function delete()
    {
        $request = new Curl();

        $request->setHeader('x-api-key', 'ShO0V5WzK43TawOinsvYg9P67ApzhtZi7BiAb8Zn');
        $request->setHeader('Content-Type', 'application/json');

        $request->delete('https://' . $this->api . '/provision', [], [
            'hostname' => $this->hostnameToUpdate . '.'
        ]);

        if (!$request->error) {
            $this->cache->delete($this->hostnameToUpdate);
        }

        return $this;
    }
}