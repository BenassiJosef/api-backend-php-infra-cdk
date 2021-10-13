<?php
/**
 * Created by jamieaitken on 20/11/2017 at 16:05
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Utils;


use App\Models\Integrations\IPInfo\IPInfo;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class _GeoInfoViaIP
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createOrFetchRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $ip = $request->getHeader('X-Forwarded-For');

        if (array_key_exists('ip', $queryParams)) {
            $ip = $queryParams['ip'];
        }

        if (is_array($ip) && !empty($ip)) {
            $ip = $ip[0];
        }
        if (is_string($ip) && stripos($ip, ',') !== false) {
            $mutipleIps = explode(',', $ip);
            if (count($mutipleIps) >= 2) {
                $ip = $mutipleIps[0];
            }
        } else {
            if (is_null($ip) || empty($ip)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        $send = $this->createOrFetch($ip);

        return $response->withJson($send, $send['status']);
    }

    public function createOrFetch($ip)
    {

        $ipRecord = $this->em->getRepository(IPInfo::class)->findOneBy([
            'ip' => $ip
        ]);

        if (is_object($ipRecord)) {
            return Http::status(200, $ipRecord->countryCode);
        }

        $request = new Curl();
        $request->get("http://ipinfo.io/" . $ip . "/geo");

        if ($request->error) {
            return Http::status(200, 'GB');
        }

        $commaPos = strpos($request->response->loc, ',');

        $latitude = (float)substr($request->response->loc, 0, $commaPos - 1);

        $longitude = (float)substr($request->response->loc, $commaPos + 1, strlen($request->response->loc));

        $newIpRecord = new IPInfo($request->response->ip, $latitude, $longitude, $request->response->country);
        $this->em->persist($newIpRecord);
        $this->em->flush();

        return Http::status(200, $newIpRecord->countryCode);
    }
}