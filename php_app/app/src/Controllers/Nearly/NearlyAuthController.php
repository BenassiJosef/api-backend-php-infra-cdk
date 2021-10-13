<?php

/**
 * Created by jamieaitken on 14/02/2018 at 10:19
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;

use App\Controllers\Clients\_ClientsController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\OpenMesh\OpenMeshNearlySettings;
use App\Controllers\Integrations\Radius\RadiusNearlySettings;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Integrations\UniFi\_UniFiController;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\Nearly\Impressions;
use App\Models\Nearly\ImpressionsAggregate;
use App\Models\RadiusVendor;
use App\Utils\Http;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyAuthController
{
    protected $em;
    protected $mixpanel;


    public function __construct(EntityManager $em)
    {
        $this->em       = $em;
        $this->mixpanel = new _Mixpanel();
    }

    public function createSessionRoute(Request $request, Response $response)
    {

        $body = $request->getParsedBody();

        $optOutLocation  = false;
        $optOutMarketing = false;

        $getLocationType = $this->em->createQueryBuilder()
            ->select('u.type')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $body['serial'])
            ->getQuery()
            ->getArrayResult();

        if ($getLocationType[0]['type'] === 0) {
            if (isset($body['locationOpt'])) {
                if ($body['locationOpt'] === true) {
                    $optOutLocation  = true;
                    $optOutMarketing = true;

                    $body['shadowedMac'] = hash('sha512', $body['mac']);
                }
            }

            if (isset($body['marketingOpt'])) {
                if ($body['marketingOpt'] === true) {
                    $optOutMarketing = true;
                }
            }
            if (isset($body['email']) && ($optOutMarketing === true || $optOutLocation === true)) {
                $body['email'] = hash('sha512', $body['email']);
            } elseif (!isset($body['email']) && ($optOutMarketing === true || $optOutLocation === true)) {
                $body['email'] = hash('sha512', 'random');
            }

            $client = new QueueSender();

            $client->sendMessage([
                'id'              => $body['id'],
                'optOutLocation'  => $optOutLocation,
                'optOutMarketing' => $optOutMarketing,
                'serial'          => $body['serial']
            ], QueueUrls::OPT_OUT);
        }

        $this->runValidation($request, $body);

        $link = $this->createSession($body);

        if (isset($body['impressionId'])) {
            $impressionConverted = $this->em->getRepository(Impressions::class)->findOneBy([
                'id' => $body['impressionId']
            ]);

            if (is_object($impressionConverted)) {
                $impressionConverted->profileId         = $body['id'];
                $impressionConverted->converted         = true;
                $impressionConverted->conversionCreated = new \DateTime();
            }

            $hour  = date('H');
            $day   = date('j');
            $week  = date('W');
            $month = date('m');
            $year  = date('Y');

            $impressionsAggregate = $this->em->getRepository(ImpressionsAggregate::class)->findOneBy([
                'serial' => $body['serial'],
                'hour'   => $hour,
                'day'    => $day,
                'week'   => $week,
                'month'  => $month,
                'year'   => $year
            ]);

            $date      = new \DateTime();
            $formatted = $date->format('Y-m-d H:00:00');

            $date = new \DateTime($formatted);


            if (is_object($impressionsAggregate)) {
                $impressionsAggregate->converted += 1;
            } else {
                $impressionsAggregate            = new ImpressionsAggregate(
                    $body['serial'],
                    $year,
                    $month,
                    $week,
                    $day,
                    $hour,
                    $date
                );
                $impressionsAggregate->converted += 1;
                $this->em->persist($impressionsAggregate);
            }

            $this->em->flush();
        }

        $this->em->clear();

        return $response->withJson($link, 200);
    }

    public function runValidation(Request $request, array $body)
    {
        if (strpos($body['mac'], '.') !== false) {
            $this->mixpanel->identify($body['serial'])->track('MALFORMED_REQUEST', [
                'headers'  => $request->getHeaders(),
                'contents' => $body
            ]);
        }
    }

    public function createSession(array $body)
    {
        $userData = [
            'email'     => $body['email'],
            'mac'       => $body['mac'],
            'ip'        => $body['ip'],
            'serial'    => $body['serial'],
            'type'      => $body['type'],
            'auth_time' => $body['auth_time'],
            'profileId' => $body['id']
        ];

        if (isset($body['shadowedMac'])) {
            $userData['shadowedMac'] = $body['shadowedMac'];
        }

        $vendorInfo = $this->getVendorViaSerial($body['serial']);

        $userData['method'] = $vendorInfo['vendor'];
        $userData['radius'] = $vendorInfo['radius'];

        /**
         * For Ignitenet
         */

        if (isset($body['uamip'])) {
            $body['ap'] = $body['uamip'];
        }

        /**
         * For Mikrotik
         */

        if (isset($body['link'])) {
            $body['ap'] = $body['link'];
        }

        if (empty($body['ap'])) {
            $body['ap'] = 'http://10.4.1.2/login';
        }

        $userData['ap'] = $body['ap'];

        if ($userData['method'] === 'aruba') {
            $userData['method'] = 'radius';
        }

        if (
            $userData['method'] === 'ruckus-smartzone' ||
            $userData['method'] === 'ruckus-unleashed' ||
            $userData['method'] === 'meraki' ||
            $userData['method'] === 'ruckus' ||
            $userData['method'] === 'aerohive' ||
            $userData['method'] === 'openmesh' ||
            $userData['method'] === 'engenius' ||
            $userData['method'] === 'ligowave' ||
            $userData['method'] === 'zyxel-nebula' ||
            $userData['method' === 'plasmacloud'] ||
            $userData['method'] === 'dlink' ||
            $userData['method'] === 'tplink'
        ) {
            $userData['radius'] = true;
        }

        if (
            $userData['method'] === 'openmesh' ||
            $userData['method'] === 'ligowave' ||
            $userData['method'] === 'radius'
        ) {
            if (!isset($body['port'])) {

                $this->mixpanel->track('nearly_error_port', [
                    'input'  => $userData,
                    'reason' => 'Requires Port'
                ]);

                return Http::status(409, 'REQUIRES_PORT');
            }

            if (!isset($body['challenge'])) {
                $this->mixpanel->track('nearly_error_challenge', [
                    'input'  => $userData,
                    'reason' => 'Requires Challenge'
                ]);

                return Http::status(409, 'REQUIRES_CHALLENGE');
            }

            $userData['port']      = $body['port'];
            $userData['challenge'] = $body['challenge'];
        }

        $ua = $_SERVER['HTTP_USER_AGENT'];

        $dd = new DeviceDetector($ua);
        $dd->discardBotInformation();
        $dd->skipBotDetection();
        $dd->parse();

        $userData['agent'] = [
            'browser' => $dd->getClient(),
            'os'      => $dd->getOs(),
            'device'  => [
                'mobile'     => $dd->getDevice(),
                'type'       => $dd->getDeviceName(),
                'brand'      => $dd->getBrandName(),
                'short_name' => $dd->getBrand(),
                'model'      => $dd->getModel()
            ]
        ];

        if (isset($userData['shadowedMac'])) {
            $userData['agent']['device']['mac'] = $userData['shadowedMac'];
        } else {
            $userData['agent']['device']['mac'] = $userData['mac'];
        }

        $newClientSession = new _ClientsController($this->em);
        $request          = $newClientSession->createSession($userData);

        if ($request['status'] !== 200) {

            $this->mixpanel->track('nearly_error', [
                'input'  => $userData,
                'reason' => $request['message']
            ]);

            return $request['message'];
        }

        $linkToReturn = [];

        switch ($userData['method']) {
            case 'mikrotik':
                $linkToReturn = $this->mikrotikLink($userData['ap'], $body['type']);
                break;
            case 'unifi':
                $linkToReturn = $this->unifiAuth($userData);
                break;
            case 'openmesh':
                $linkToReturn = $this->openMeshLink($userData);
                break;
            case 'plasmacloud':
                $linkToReturn = $this->openMeshLink($userData);
                break;
            case 'ligowave':
                $linkToReturn = $this->genericRadius($userData);
                break;
            case 'radius':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'dlink':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'tplink':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'ruckus-smartzone':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'ruckus-unleashed':
                $linkToReturn = $this->genericAuth($userData);
                break;
            case 'ruckus':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'meraki':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'ignitenet':
                $linkToReturn = $this->igniteNetLink($userData);
                break;
            case 'engenius':
                $linkToReturn = $this->wisprLink($userData);
                break;
            case 'aerohive':
                $linkToReturn = $this->genericAuth($userData);
                break;
        }


        $linkToReturn['serial'] = $body['serial'];

        return $linkToReturn;
    }

    public function getVendorViaSerial(string $serial)
    {

        $hasInform = $this->em->createQueryBuilder()
            ->select('u.vendor')
            ->from(Inform::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($hasInform)) {
            $vendor = strtolower($hasInform[0]['vendor']);
            if ($vendor !== 'openmesh' && $vendor !== 'meraki' && $vendor !== 'aruba') {
                return [
                    'radius' => false,
                    'vendor' => $vendor
                ];
            }
        }

        $getVendor = $this->em->createQueryBuilder()
            ->select('u.vendor')
            ->from(RadiusVendor::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult();

        return [
            'radius' => true,
            'vendor' => strtolower($getVendor[0]['vendor'])
        ];
    }


    public function openMeshLink(array $postData)
    {
        $openmesh = new OpenMeshNearlySettings($this->em);

        $openmesh->getLandingAndSecret($postData['serial']);

        if ($postData['radius'] === true) {
            $link = $openmesh->radiusLink(
                $postData['ap'],
                $postData['port'],
                $postData['challenge'],
                $postData['profileId'],
                $postData['serial']
            );
        } else {
            $link = $openmesh->link(
                $postData['ap'],
                $postData['port'],
                $postData['mac'],
                $postData['challenge']
            );
        }

        $link .= '&redir=' . urlencode($openmesh->landing);

        return [
            'method'      => 'openmesh',
            'online'      => true,
            'redirectUri' => $link,
            'serial'      => $postData['serial']
        ];
    }

    public function wisprLink(array $postData)
    {
        $defaults = [
            'method'      => $postData['method'],
            'redirectUri' => $postData['ap'],
            'landingUri'  => 'http://nearly.online/landing/' . $postData['serial'],
            'username'    => $postData['profileId'] . $postData['serial'],
            'password'    => $postData['profileId'],
            'online'      => true
        ];

        foreach ($postData as $key => $value) {
            if (!isset($defaults[$key])) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public function genericRadius(array $postData)
    {
        $newRadiusNearly = new RadiusNearlySettings($this->em);
        $newRadiusNearly->setSecret($postData['serial']);

        return [
            'redirectUri' => $newRadiusNearly->radiusLink(
                $postData['ap'],
                $postData['port'],
                $postData['challenge'],
                $postData['profileId'],
                $postData['serial']
            ),
            'online'      => true,
            'method'      => $postData['method']
        ];
    }

    public function unifiAuth($data)
    {
        $newUnifi = new _UniFiController($this->em);

        $auth = $newUnifi->auth($data);

        $return = [
            'method' => 'unifi',
            'online' => false
        ];

        if ($auth['status'] !== 200) {
            $return['landingUri'] = 'https://google.com';

            return $return;
        }

        $return['landingUri'] = $auth['message']['landing'];

        return $return;
    }

    public function getMikrotikRedirect(string $link, string $type, array $params = [])
    {
        $token    = 'TXGZ3SbcFM6VHQCBGKuYPhiRZcwyn8UevMdNJ3DXL4MyFDnSXi';
        $username = 'freewifi';

        if ($type === 'paid') {
            $token    = 'paidpass';
            $username = "paid";
        }

        return [
            'method'      => 'mikrotik',
            'redirectUri' => $link . "?username=" . $username . "&password=" . $token,
            'online'      => true
        ];
    }

    public function igniteNetLink(array $body)
    {
        $token    = 'TXGZ3SbcFM6VHQCBGKuYPhiRZcwyn8UevMdNJ3DXL4MyFDnSXi';
        $username = 'freewifi';

        if ($body['type'] === 'paid') {
            $token    = 'paidpass';
            $username = "paid";
        }

        return [
            'method'      => 'ignitenet',
            'redirectUri' => 'http://' . $body['ap'] . "?username=" . $username . "&password=" . $token,
            'online'      => true
        ];
    }

    public function genericAuth(array $meta = [])
    {
        return [
            'method'      => $meta['method'],
            'online'      => true,
            'redirectUri' => 'http://' . $meta['ap'] . '/login?username=' .
                $meta['profileId'] . $meta['serial'] . '&password=' . $meta['profileId']
        ];
    }
}
