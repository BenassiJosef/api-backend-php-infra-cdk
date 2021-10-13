<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 12/01/2017
 * Time: 10:13
 */

namespace App\Controllers\Nearly;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\User\UserDevice;
use App\Models\UserData;
use App\Utils\Http;
use App\Utils\MacFormatter;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class _NearlyDevicesController
{
    protected $em;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * _NearlyDevicesController constructor.
     * @param Logger $logger
     * @param EntityManager $em
     */
    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function verifyUserRoute(Request $request, Response $response)
    {
        $body   = $request->getQueryParams();
        $verify = $this->verify($body['email'], $body['serial'], $body['mac']);

        return $response->withStatus($verify['status'])->write(
            json_encode($verify)
        );
    }

    public function getPaidDevicesRoute(Request $request, Response $response)
    {
        $body = $request->getQueryParams();
        $send = $this->paidDevices($body['profileId'], $body['serial']);

        $this->em->clear();

        return $response->withStatus($send['status'])->write(
            json_encode($send)
        );
    }

    public function updatePaidDevicesRoute(Request $request, Response $response)
    {
        $body = $request->getQueryParams();
        $mac  = $request->getAttribute('mac');
        $send = $this->updateDevices($body['serial'], $mac);

        $this->em->clear();

        return $response->withStatus($send['status'])->withJson($send);
    }

    public function getAp(Request $request, Response $response)
    {

        $ssid                      = $request->getQueryParams()['ssid'];
        $mac                       = $request->getAttribute('mac');
        $locationDevicesController = new _LocationsDevicesController($this->em);
        $send                      = $locationDevicesController->getByMac($mac);

        if ($send['status'] !== 200) {
            $this->em->clear();

            return $response->withJson($send, $send['status']);
        }

        /**
         * DEFAULT SITE NEED TO CHECK UNIFI MULTISITE
         */
        $location = $this->em->getRepository(UnifiLocation::class)->findOneBy([
            'serial'    => $send['message']['serial'],
            'multiSite' => true,
        ]);

        if (is_null($location)) {
            $this->em->clear();

            return $response->withJson($send, $send['status']);
        }

        $multiLocation = $this->em->getRepository(UnifiLocation::class)->findOneBy([
            'unifiControllerId' => $location->unifiControllerId,
            'multiSite'         => true,
            'multiSiteSsid'     => $ssid
        ]);

        if (!is_null($multiLocation)) {
            $send['message']['serial'] = $multiLocation->serial;
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function checkDataUsageRoute(Request $request, Response $response)
    {

        $send = $this->checkDataUsage($request->getAttribute('profileId'), $request->getAttribute('serial'),
            $request->getAttribute('limit'));

        $this->em->clear();

        return $response->withStatus($send['status'])->write(
            json_encode($send)
        );
    }

    public function verify($email = '', $serial = '', $mac = '')
    {
        $this->logger->info("Verifying $email on $serial");
        $query = 'SELECT 
                  min(up.id), 
                  SUM(up.duration) AS duration,
                  SUM(up.payment_amount) AS cost, 
                  SUM(up.devices) AS devices,
                  COUNT(up.id) AS  payments,
                  min(up.profileId) AS profileId,
                  min(up.creationdate) as creationdate,
                  min(up.duration) as duration
            FROM 
            user_profile u
            JOIN user_payments up ON u.id = up.profileId
            WHERE (u.email = :email OR u.email = :shadowMail)
            AND up.creationdate + INTERVAL duration HOUR > NOW()
            AND serial = :serial';

        $builder = $this->em->getConnection();
        $stmt    = $builder->prepare($query);

        $stmt->execute([
            'email'      => $email,
            'shadowMail' => hash('sha512', $email),
            'serial'     => $serial
        ]);

        $join = $stmt->fetch();

        if (!empty($join)) {
            if ((int)$join['payments'] === 0) {
                $mp = new _Mixpanel();
                $mp->identify($serial)->track('nearly_payment', [
                    'code'   => 402,
                    'mac'    => $mac,
                    'serial' => $serial,
                    'email'  => $email
                ]);
                $this->logger->info("No payment plan in place for $email on $serial");
                return Http::status(402, 'NO_PAYMENT_PLAN');
            }

            $limitDevices           = (int)$join['devices'];
            $currentlyAuthenticated = $this->paidDevices($join['profileId'], $serial);

            if ($limitDevices <= $currentlyAuthenticated['message']['device_count']) {
                $mp = new _Mixpanel();
                $mp->identify($serial)->track('nearly_payment', [
                    'code'                 => 400,
                    'mac'                  => $mac,
                    'serial'               => $serial,
                    'email'                => $email,
                    'device_limit'         => $limitDevices,
                    'current_device_count' => $currentlyAuthenticated['message']['device_count']
                ]);
                $this->logger->info("Reached plan limit for $email on $serial");
                return Http::status(400, 'HAVE_REACHED_PLAN_LIMITS');
            }

            $this->em->createQueryBuilder()
                ->update(UserData::class, 'u')
                ->set('u.auth', 1)
                ->where('u.serial = :serial')
                ->andWhere('u.mac = :mac OR u.mac = :shadowMac')
                ->andWhere('u.type = :type')
                ->setParameter('serial', $serial)
                ->setParameter('mac', $mac)
                ->setParameter('shadowMac', hash('sha512', $mac))
                ->setParameter('type', 'paid')
                ->getQuery()
                ->execute();

            $this->logger->info("Verification success for $email on $serial");
            return Http::status(200, [
                'devices_authenticated' => count($currentlyAuthenticated['message']['devices']),
                'id'                    => $join['profileId'],
                'plan_limit'            => $limitDevices
            ]);
        }

        return false;
    }

    public function paidDevices($profileId = '', $serial = '')
    {
        $devices = $this->em->createQueryBuilder()
            ->select('u.mac, min(u.brand) as brand, min(u.model) as model')
            ->from(UserDevice::class, 'u')
            ->join(UserData::class, 'ud', 'WITH', 'u.mac = ud.mac')
            ->where('ud.serial = :serial')
            ->andWhere('ud.profileId = :id')
            ->andWhere('ud.auth = :a')
            ->andWhere('ud.type = :t')
            ->setParameter('serial', $serial)
            ->setParameter('id', $profileId)
            ->setParameter('a', 1)
            ->setParameter('t', 'paid')
            ->groupBy('u.mac')
            ->getQuery()
            ->getArrayResult();

        $this->em->flush();

        if (!empty($devices)) {

            foreach ($devices as $device) {
                if (strlen($device['mac']) !== 128) {
                    $key = array_search(hash('sha512', $device['mac']), array_column($devices, 'mac'));
                    if (!is_bool($key)) {
                        array_splice($devices, $key, 1);
                    }
                }
            }

            return Http::status(200, [
                'devices'      => $devices,
                'device_count' => count($devices)
            ]);
        }

        return Http::status(200, [
            'device_count' => 0,
            'devices'      => []
        ]);
    }

    public function updateDevices($serial = '', $mac = '')
    {
        $update = $this->em->createQueryBuilder()
            ->update(UserData::class, 'u')
            ->set('u.auth', ':false')
            ->where('u.serial = :serial')
            ->andWhere('u.mac = :mac OR u.mac = :shadowMac')
            ->andWhere('u.type = :t')
            ->setParameter('false', 0)
            ->setParameter('serial', $serial)
            ->setParameter('mac', $mac)
            ->setParameter('shadowMac', hash('sha512', $mac))
            ->setParameter('t', 'paid')
            ->getQuery()
            ->execute();

        $this->em->flush();

        if ($update >= 1) {
            return Http::status(200, 'UPDATED');
        }

        return Http::status(404, 'COULD_NOT_LOCATE_DEVICE');
    }

    /**
     * @param string $serial
     * @param string $mac
     * @return array
     */

    public function checkAuth($serial = '', $mac = '')
    {
        $select = $this->em->getRepository(UserData::class)->findOneBy([
            'serial' => $serial,
            'mac'    => MacFormatter::format($mac),
            'type'   => 'free'
        ]);

        if (is_object($select)) {
            return Http::status(200, $select->getArrayCopy());
        }

        return Http::status(200, [
            'auth' => true
        ]);
    }

    public function checkDataUsage($profileId = '', $serial = '', $sizeLimit = 0)
    {
        $date  = new \DateTime();
        $check = $this->em->createQueryBuilder()
            ->select('SUM(u.dataDown) dd')
            ->from(UserData::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.profileId = :id')
            ->andWhere('u.timestamp > :past')
            ->setParameter('serial', $serial)
            ->setParameter('id', $profileId)
            ->setParameter('past', $date->modify('-1 month'))
            ->getQuery()
            ->getArrayResult();


        $usage = 0;

        if (!empty($check)) {
            if (!is_null($check[0]['dd'])) {
                $toMB = ($check[0]['dd'] / 1024) / 1024;

                $usage = $check[0]['dd'];
                if ($toMB >= $sizeLimit) {
                    return Http::status(402, $usage);
                }
            }
        }

        return Http::status(200, $usage);
    }
}
