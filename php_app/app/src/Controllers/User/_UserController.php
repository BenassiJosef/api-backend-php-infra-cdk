<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/05/2017
 * Time: 13:07
 */

namespace App\Controllers\User;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\UniFi\_Client;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Controllers\Nearly\NearlyProfileController;
use App\Models\Device\DeviceBrowser;
use App\Models\Locations\LocationSettings;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEvents;
use App\Models\User\UserAgent;
use App\Models\User\UserBlocked;
use App\Models\User\UserDevice;
use App\Models\UserData;
use App\Models\UserPayments;
use App\Models\UserProfile;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class _UserController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function userSearchRoute(Request $request, Response $response)
    {
        $send = $this->userSearch($request->getAttribute('user')['access'], $request->getQueryParams());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function userSummaryRoute(Request $request, Response $response)
    {
        $send = $this->userSummary($request->getAttribute('id'), $request->getAttribute('user')['access']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadUserDevicesRoute(Request $request, Response $response)
    {
        $send = $this->loadUserDevices($request->getAttribute('id'), $request->getAttribute('user')['access']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function changeAuthorisedStateRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $send = $this->changeAuthorisedState($body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }


    public function loadUserConnectionLogRoute(Request $request, Response $response)
    {
        $send = $this->loadUserConnectionLog(
            $request->getAttribute('id'),
            $request->getQueryParams(),
            $request->getAttribute('user')['access']
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadUserDataUsageRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $send        = $this->loadUserDataUsage(
            $request->getAttribute('id'),
            $queryParams['start'],
            $queryParams['end'],
            $request->getAttribute('user')['access']
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadUserMarketingDataRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $send = $this->loadUserMarketingData(
            $request->getAttribute('id'),
            $queryParams['start'],
            $queryParams['end'],
            $request->getAttribute('user')['access']
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function exportUserReportRoute(Request $request, Response $response)
    {
        $send = $this->exportUserReport(
            $request->getAttribute('id'),
            $request->getAttribute('user')['access'],
            $request->getAttribute('user')
        );

        $this->em->clear();

        return $response->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Type', 'application/download')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="' . basename('Nearly Online Profile Export.pdf') . '"'
            )
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->write($send['message']);
    }

    public function blockUserRoute(Request $request, Response $response)
    {
        $send = $this->blockUser($request->getParsedBody());

        return $response->withJson($send, $send['status']);
    }

    private function userSearch(array $serials, array $params)
    {

        if (sizeof($params) === 1 && isset($params['offset'])) {
            return Http::status(404, 'REQUIRES_BASE_PARAM');
        }
        $sql = $this->em->createQueryBuilder()
            ->select('
            UNIX_TIMESTAMP(ud.lastupdate) * 1000 as time,
            ud.mac,
            ud.timestamp,
            ud.lastupdate,
            ud.serial,
            up.id,
            up.email,
            up.first,
            up.last')
            ->from(UserProfile::class, 'up')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
            ->where('ud.serial IN (:serials)')
            ->setParameter('serials', $serials);
        if (isset($params['serial'])) {
            $params['serial'] = str_replace(' ', '', $params['serial']);
            $sql
                ->andWhere('ud.serial = :serial')
                ->setParameter('serial', $params['serial']);
        }

        if (isset($params['email'])) {
            $params['email'] = str_replace(' ', '', $params['email']);
            $sql
                ->andWhere('up.email LIKE :email')
                ->setParameter('email', $params['email'] . '%');
        }

        if (isset($params['mac'])) {
            $sql
                ->andWhere('ud.mac LIKE :mac')
                ->setParameter('mac', $params['mac'] . '%');
        }

        $sql
            ->groupBy('ud.profileId')
            ->orderBy('ud.timestamp', 'DESC')
            ->setFirstResult($params['offset'])
            ->setMaxResults(25);

        $results = new Paginator($sql);
        $results->setUseOutputWalkers(false);

        $users = $results->getIterator()->getArrayCopy();

        if (empty($users)) {
            return Http::status(204, 'NO_USERS_FOUND');
        }

        $response = [
            'users'      => $users,
            'totalUsers' => count($results),
            'nextOffset' => $params['offset'] + 25,
            'hasMore'    => false
        ];

        if ($params['offset'] <= $response['totalUsers'] && count($users) !== $response['totalUsers']) {
            $response['hasMore'] = true;
        }

        return Http::status(200, $response);
    }

    private function userSummary(string $id, array $serials)
    {
        $sqlUser = $this->em->createQueryBuilder()
            ->select(
                'up.id,
                up.email,
                up.first,
                up.last,
                up.gender,
                up.phone,
                up.postcode,
                up.postcodeValid,
                up.opt,
                up.age,
                up.birthMonth,
                up.birthDay,
                up.ageRange,
                up.verified'
            )
            ->from(UserProfile::class, 'up')
            ->leftJoin(UserData::class, 'ud', 'WITH', 'up.id = ud.profileId')
            ->where('up.id = :id')
            ->andWhere('ud.serial IN (:serials)')
            ->setParameter('id', $id)
            ->setParameter('serials', $serials)
            ->orderBy('ud.lastupdate', 'DESC')
            ->getQuery()
            ->getArrayResult();


        if (empty($sqlUser)) {
            return Http::status(204, 'COULD_NOT_LOCATE_USER');
        }

        $sqlUser = $sqlUser[0];

        $sqlUserPayments = $this->em->createQueryBuilder()
            ->select('upa.serial, upa.creationdate, upa.paymentAmount, l.alias, upa.id')
            ->from(UserPayments::class, 'upa')
            ->leftJoin(LocationSettings::class, 'l', 'WITH', 'upa.serial = l.serial')
            ->where('upa.serial IN (:serial)')
            ->andWhere('upa.profileId = :id')
            ->setParameter('id', $id)
            ->setParameter('serial', $serials)
            ->groupBy('upa.id')
            ->orderBy('upa.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $sqlUserConnections = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(ud.dataUp),0) as totalUpload, 
                COALESCE(SUM(ud.dataDown),0) as totalDownload,
                SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
                MAX(ud.timestamp) as connectedAt,
                MAX(ud.lastupdate) as lastSeenAt,
                ud.serial,
                ud.mac,
                ud.auth,
                deviceTable.model as device,
                browserTable.name as browser,
                ud.type,
                ub.id as blocked,
                ls.alias')
            ->from(UserData::class, 'ud')
            ->leftJoin(UserDevice::class, 'deviceTable', 'WITH', 'deviceTable.mac = ud.mac')
            ->leftJoin(UserAgent::class, 'agent', 'WITH', 'agent.userDeviceId = deviceTable.id')
            ->leftJoin(DeviceBrowser::class, 'browserTable', 'WITH', 'browserTable.id = agent.deviceBrowserId')
            ->leftJoin(UserBlocked::class, 'ub', 'WITH', 'ud.mac = ub.mac AND ud.serial = ub.serial')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'ud.serial = ls.serial')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.profileId = :id')
            ->setParameter('id', $id)
            ->setParameter('serial', $serials)
            ->groupBy('ud.id')
            ->orderBy('ud.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $totals = [
            'download'         => 0,
            'upload'           => 0,
            'logins'           => sizeof($sqlUserConnections),
            'uptime'           => 0,
            'amountOfPayments' => 0,
            'amountSpent'      => 0
        ];

        $events   = [];
        $visits   = [];
        $payments = [];
        $devices  = [];

        foreach ($sqlUserConnections as $k => $value) {

            $connected = new \DateTime($value['connectedAt']);
            $connected->modify('00:00:00');
            $connectedAtTimestamp = $connected->getTimestamp();

            $insertKey = array_search($connectedAtTimestamp, array_column($events, 'unix'));

            if ($insertKey === false) {
                $events[] = [
                    'loginEvents'          => [],
                    'paymentEvents'        => [],
                    'deviceAddedEvents'    => [],
                    'userRegisteredEvents' => [],
                    'unix'                 => $connectedAtTimestamp
                ];
            }

            $insertKey = array_search($connectedAtTimestamp, array_column($events, 'unix'));


            $blocked = false;
            if (!is_null($value['blocked'])) {
                $blocked = true;
            }

            $value['blocked'] = $blocked;

            $timeout = $value['timeout'];
            $enabled = $value['enabled'];

            if (is_null($timeout)) {
                $timeout = false;
            }

            if (is_null($enabled)) {
                $enabled = false;
            }

            unset($value['timeout'], $value['enabled']);


            $value['timeoutSettings'] = [
                'timeout' => $timeout,
                'enabled' => $enabled
            ];

            $value['eventType']                  = 'LOGIN';
            $value['uptime']                     = (int)$value['uptime'];
            $events[$insertKey]['loginEvents'][] = $value;


            if (!in_array($value['mac'], $devices)) {
                $events[$insertKey]['deviceAddedEvents'][] = [
                    'isNewDevice' => true,
                    'mac'         => $value['mac'],
                    'device'      => $value['device'],
                    'browser'     => $value['browser'],
                    'eventType'   => 'DEVICE_ADDED',
                    'alias'       => $value['alias']
                ];
                $devices[]                                 = $value['mac'];
            }

            if (!in_array($value['serial'], $visits)) {
                $events[$insertKey]['userRegisteredEvents'][] = [
                    'isFirstVisit' => true,
                    'registeredAt' => $value['connectedAt'],
                    'eventType'    => 'REGISTRATION',
                    'serial'       => $value['serial'],
                    'alias'        => $value['alias']
                ];

                $visits[] = $value['serial'];
            }

            $totals['download'] += $sqlUserConnections[$k]['totalDownload'];
            $totals['upload']   += $sqlUserConnections[$k]['totalUpload'];
            $totals['uptime']   += $sqlUserConnections[$k]['uptime'];
        }

        foreach ($sqlUserPayments as $payment) {
            $totals['amountSpent'] += $payment['paymentAmount'];

            $copyOfCreationDate = clone $payment['creationdate'];

            $copyOfCreationDate->modify('00:00:00');
            $connectedAtTimestamp = $copyOfCreationDate->getTimestamp();
            $insertKey            = array_search($connectedAtTimestamp, array_column($events, 'unix'));

            if (!isset($events[$insertKey])) {
                $events[$insertKey] = [
                    'loginEvents'       => [],
                    'paymentEvents'     => [],
                    'deviceAddedEvents' => [],
                    'unix'              => $connectedAtTimestamp
                ];
            }

            $insertKey = array_search($connectedAtTimestamp, array_column($events, 'unix'));


            $value['eventType'] = 'PAYMENT';

            if (!in_array($payment['serial'], $payments)) {
                $events[$insertKey]['paymentEvents'][] = array_merge($payment, ['isFirstPayment' => true]);
                $payments[]                            = $payment['serial'];
            } else {
                $events[$insertKey]['paymentEvents'][] = array_merge($payment, ['isFirstPayment' => false]);
            }
        }

        $totals['amountOfPayments'] = sizeof($sqlUserPayments);

        $response = [
            'profile' => $sqlUser,
            'events'  => $events,
            'totals'  => $totals
        ];

        return Http::status(200, $response);
    }

    private function loadUserDevices(string $id, array $serials)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('
            up.email,
            ud.dataDown,
            ud.dataUp,
            ud.mac,
            ud.auth,
            deviceTable.model as device,
            browserTable.name as browser,
            ud.type,
            ud.serial,
            ub.id as blocked, 
            ud.lastupdate')
            ->from(UserData::class, 'ud')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = ud.profileId')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.profileId = :id')
            ->setParameter('id', $id)
            ->setParameter('serial', $serials)
            ->groupBy('ud.serial')
            ->addGroupBy('ud.mac')
            ->addGroupBy('ud.auth')
            ->addGroupBy('ud.type')
            ->orderBy('ud.lastupdate', 'DESC')
            ->getQuery()
            ->getArrayResult();
        if (empty($sql)) {
            return Http::status(204, 'NO_DEVICES');
        }

        $response = [];



        return Http::status(200, $response);
    }

    public function changeAuthorisedState(array $body)
    {
        $required = ['auth', 'serial', 'mac'];

        $validate = Validation::pastRouteBodyCheck($body, $required);
        if (is_array($validate)) {
            return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
        }

        $type = null;

        $getCurrentTypeOfSite = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $body['serial']
        ]);

        switch ($getCurrentTypeOfSite->type) {
            case 0:
                $type = 'free';
                break;
            case 1:
                $type = 'paid';
                break;
            case 2:
                $type = 'hybrid';
                break;
        }

        $update = $this->em->createQueryBuilder()
            ->update(UserData::class, 'ud')
            ->set('ud.auth', ':auth')
            ->where('ud.serial = :serial')
            ->andWhere('ud.mac = :mac')
            ->andWhere('ud.type = :type')
            ->setParameter('auth', $body['auth'])
            ->setParameter('serial', $body['serial'])
            ->setParameter('mac', $body['mac'])
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();

        if ((bool)$body['auth'] === false) {
            $this->deauth($body['mac'], $body['serial']);
        }

        if ($update >= 1) {
            return Http::status(200, $body);
        }

        return Http::status(400, 'FAILED_TO_UPDATE');
    }


    public function loadUserConnectionLog(string $profileId, array $queryParams, array $serials)
    {
        $allowedOrdersAlias = [
            'up'   => 'totalUpload',
            'dl'   => 'totalDownload',
            'lud'  => 'ud.lastupdate',
            'udts' => 'ud.timestamp'
        ];

        $allowedSortAlias = [
            'up'   => 'ASC',
            'down' => 'DESC'
        ];

        $sort   = 'DESC';
        $order  = 'ud.lastupdate';
        $offset = 0;

        $allowedOrders = ['up', 'dl', 'lud', 'udts'];
        $allowedSorts  = ['up', 'down'];
        if (isset($queryParams['order']) && !in_array($queryParams['order'], $allowedOrders)) {
            return Http::status(404, 'INVALID_ORDER_QUERY_PARAM');
        }

        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        if (!isset($queryParams['order'])) {
            $order = 'ud.lastupdate';
        } elseif (isset($queryParams['order']) && in_array($queryParams['order'], $allowedOrders)) {
            $order = $allowedOrdersAlias[$queryParams['order']];
        }

        if (isset($queryParams['sort']) && in_array($queryParams['sort'], $allowedSorts)) {
            $sort = $allowedSortAlias[$queryParams['sort']];
        }


        $sql = '
        ud.serial,
        COALESCE(SUM(ud.dataUp),0) as totalUpload, 
        COALESCE(SUM(ud.dataDown),0) as totalDownload,
        SUM(TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime, 
        ud.lastupdate,
        ud.timestamp';

        $maxResults      = 50;
        $userConnections = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(UserData::class, 'ud')
            ->join(UserProfile::class, 'up', 'WITH', 'ud.profileId = up.id')
            ->where('ud.serial IN (:serial)')
            ->andWhere('ud.profileId = :id')
            ->setParameter('serial', $serials)
            ->setParameter('id', $profileId)
            ->setFirstResult($offset)
            ->setMaxResults($maxResults)
            ->groupBy('ud.id')
            ->orderBy($order, $sort);

        $results = new Paginator($userConnections);
        $results->setUseOutputWalkers(false);

        $userConnections = $results->getIterator()->getArrayCopy();

        if (empty($userConnections)) {
            return Http::status(204, 'NO_DATA_FOR_USER_FOUND');
        }

        $return = [
            'table'       => $userConnections,
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + $maxResults
        ];

        if ($offset <= $return['total'] && count($userConnections) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($userConnections as $key => $connection) {
            foreach ($connection as $k => $v) {
                if (is_numeric($v)) {
                    $userConnections[$key][$k] = (int)round($v);
                }
            }
        }

        return Http::status(200, $return);
    }

    public function loadUserDataUsage(string $id, string $start, string $end, array $serials)
    {

        $startTime = new \DateTime();
        $endTime   = new \DateTime();

        $startTime->setTimestamp($start);
        $endTime->setTimestamp($end);

        $startTime->setTime(0, 0, 0);
        $endTime->setTime(23, 59, 59);

        $query = $this->em->createQueryBuilder()
            ->select('
            COALESCE(SUM(u.dataUp),0) as totalUpload, 
            COALESCE(SUM(u.dataDown),0) as totalDownload,
            u.serial')
            ->from(UserData::class, 'u')
            ->where('u.serial IN (:serials)')
            ->andWhere('u.profileId = :id')
            ->andWhere('u.timestamp BETWEEN :start AND :end')
            ->setParameter('serials', $serials)
            ->setParameter('id', $id)
            ->setParameter('start', $startTime)
            ->setParameter('end', $endTime)
            ->groupBy('u.serial')
            ->getQuery()
            ->getArrayResult();

        if (empty($query)) {
            return Http::status(204);
        }

        return Http::status(200, $query);
    }

    public function loadUserMarketingData(string $id, string $start, string $end, array $serials)
    {
        $startTime = new \DateTime();
        $endTime   = new \DateTime();

        $startTime->setTimestamp($start);
        $endTime->setTimestamp($end);

        $startTime->setTime(0, 0, 0);
        $endTime->setTime(23, 59, 59);

        $query = $this->em->createQueryBuilder()
            ->select('me', 'mc.name')
            ->from(MarketingEvents::class, 'me')
            ->leftJoin(MarketingCampaigns::class, 'mc', 'WITH', 'me.campaignId = mc.id')
            ->where('me.serial IN (:serials)')
            ->andWhere('me.profileId = :id')
            ->andWhere('me.timestamp BETWEEN :start AND :end')
            ->setParameter('id', $id)
            ->setParameter('serials', $serials)
            ->setParameter('start', $startTime)
            ->setParameter('end', $endTime)
            ->getQuery()
            ->getArrayResult();
        if (empty($query)) {
            return Http::status(204);
        }

        $additionalInfo = [
            'emails'  => 0,
            'sms'     => 0,
            'credits' => 0
        ];

        foreach ($query as $key => $value) {
            $query[$key][0]['name'] = $value['name'];
            unset($query[$key]['name']);
            $query[$key] = $query[$key][0];
            if ($query[$key]['type'] === 'sms') {
                $additionalInfo['sms']     += 1;
                $additionalInfo['credits'] += 2;
            } elseif ($query[$key]['type'] === 'email') {
                $additionalInfo['emails']  += 1;
                $additionalInfo['credits'] += 1;
            }
        }


        $response = array_merge(['marketingData' => $query], $additionalInfo);

        return Http::status(200, $response);
    }

    public function deauth($mac, $serial)
    {
        $location = new _LocationSettingsController($this->em);
        $vendor   = $location->getVendor($serial);

        if ($vendor === false) {
            return Http::status(403, 'NO_VENDOR');
        }

        $mp = new _Mixpanel();
        $mp->register('serial', $serial)->track('client_deauth', ['mac' => $mac, 'vendor' => $vendor]);

        switch ($vendor) {
            case 'mikrotik':
                $client = new \App\Controllers\Integrations\Mikrotik\_Client($this->em);
                $client->deauth($mac, $serial);
                break;
            case 'unifi':
                $client = new _Client($this->em);
                $client->deauth($mac, $serial);
                break;
            case 'openmesh':
                break;
        }
        return Http::status(200, 'DONE');
    }

    public function exportUserReport(string $id, array $serials, array $user)
    {
        $newProfile = new NearlyProfileController($this->em);

        return $newProfile->generateDownload($id, $serials, $user);
    }

    public function blockUser(array $body)
    {

        $blockExists = $this->em->getRepository(UserBlocked::class)->findOneBy([
            'serial' => $body['serial'],
            'mac'    => $body['mac']
        ]);

        if ($body['action'] === 'unblock') {
            if (is_null($blockExists)) {
                return Http::status(400, 'USER_NOT_BLOCKED');
            }

            $this->em->remove($blockExists);
        } elseif ($body['action'] === 'block') {
            if (is_object($blockExists)) {
                return Http::status(400, 'USER_ALREADY_BLOCKED');
            }

            $newBlock = new UserBlocked($body['serial'], $body['mac']);
            $this->em->persist($newBlock);
        }

        $this->em->flush();

        return Http::status(200, $body);
    }
}
