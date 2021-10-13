<?php

namespace App\Controllers\Integrations\UniFi;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Locations\_LocationsInformController;
use App\Controllers\Schedule\_DeformController;
use App\Models\Integrations\UniFi\UnifiController;
use App\Models\Integrations\UniFi\UnifiControllerList;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\Locations\Informs\Inform;
use App\Models\Organization;
use App\Package\Nearly\NearlyInput;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Vendors\Information;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 11/12/2016
 * Time: 15:03
 */
class _UniFiController
{

    protected $em;
    protected $logger;
    protected $nearlyCache;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    /**
     * @var Information $vendors
     */
    private $vendors;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        // Temporily get logging into this class
        $logger = new Logger('app');
        $output = "%channel%.%level_name%: %message%";
        $formatter = new LineFormatter($output);

        $syslogHandler = new SyslogUdpHandler(
            "logs3.papertrailapp.com",
            29915,
            LOG_USER,
            Logger::DEBUG,
            true,
            getenv('log_name') ? getenv('log_name') : 'unknown-backend'
        );
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        $this->logger = $logger;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
        $this->organisationProvider = new OrganizationProvider($this->em);
        $this->vendors = new Information($this->em);
    }

    public function authRoute(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();

        $send = $this->auth($postData);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function syncRoute(Request $request, Response $response)
    {
        $send = $this->syncRequest($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function listSitesRoute(Request $request, Response $response)
    {

        $send = $this->listSites($request->getAttribute('controllerId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function listSsidRoute(Request $request, Response $response)
    {

        $send = $this->listSsids($request->getAttribute('controllerId'), $request->getQueryParams()['unifiId']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSiteRoute(Request $request, Response $response)
    {
        $send = $this->getSite($request->getAttribute('id'), $request->getQueryParams());

        return $response->withJson($send, $send['status']);
    }

    public function getUsersControllersRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        if (!is_null($orgId)) {
            $send = $this->getUsersControllers($orgId);
        } else {
            $send = Http::status(404, 'NO_CONTROLLERS_FOUND');
        }
        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function linkSerialWithControllerRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');
        $body = $request->getParsedBody();
        $send = $this->linkSerialWithController($serial, $body, $request->getAttribute('controllerId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function setUpControllerRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $body = $request->getParsedBody();

        $send = $this->setUpController($organization, $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteControllerRoute(Request $request, Response $response)
    {

        $send = $this->deleteController($request->getAttribute('controllerId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getControllerRoute(Request $request, Response $response)
    {
        $send = $this->getController($request->getAttribute('controllerId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateControllerRoute(Request $request, Response $response)
    {
        $send = $this->updateController($request->getAttribute('controllerId'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getCurrentSiteSetupRoute(Request $request, Response $response)
    {
        $send = $this->getCurrentSiteSetup($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function auth(NearlyInput $input)
    {
        $serial = $input->getSerial();
        $ap = $input->getAp();
        $settings = $this->settings($serial);
        $site = $settings['unifiId'];
        $timeout = (int) $settings['timeout'];

        $this->logger->debug("Authenticating serial=$serial ap=$ap");

        $unifi = new _UniFi($settings['username'], $settings['password'], $settings['hostname'], $site);

        if ($unifi->login !== true) {
            $this->logger->debug("Failed logging into UNIFI controller $serial");
            /* DO DE-FORM-LOGIC */
            $inform = $this->em->getRepository(Inform::class)->findOneBy([
                'vendor' => 'UNIFI',
                'status' => true,
                'serial' => $serial,
            ]);

            if (!is_null($inform)) {
                $deform = new _DeformController($this->em);
                $deform->setOffline($inform);
            }

            return Http::status(403, 'COULD_NOT_AUTHENTICATE_UNIFI');
        }

        $mac = $input->getMac();

        $this->logger->debug("Auth client using mac $mac on $ap");

        $result = $unifi->authClient($mac, $timeout, $ap);
        if ($result) {
            $this->logger->debug("Really Successfully authed client using mac $mac on $ap");
        } else {
            $this->logger->warning("Really Failed to auth client using mac $mac on $ap");
        }
        $host = strstr($settings['hostname'], ':', true);
        $inform = new _LocationsInformController($this->em);
        $inform->createInform($serial, gethostbyname($host), 'UNIFI', []);

        return Http::status(200, [
            'pending' => true,
        ]);
    }

    public function getSite(string $unifiSiteId, $params)
    {
/**
 * @var UnifiLocation[] $results
 */
        $results = $this->em->getRepository(UnifiLocation::class)->findBy(['unifiId' => $unifiSiteId]);
        $location = null;
        if (empty($results)) {
            return Http::status(404, 'SITE_NOT_FOUND');
        }
        if (count($results) === 1) {
            $location = $results[0];
        } else {
            foreach ($results as $result) {
                if ($result->getMultiSiteSsid() === $params['ssid']) {
                    $location = $result;
                }
            }
        }

        if (is_null($location)) {
            $location = $results[0];
        }
        $location->setInform($this->vendors->getFromSerial($location->getSerial()));

        return Http::status(200, $location);

    }

    public function getCurrentSiteSetup(string $serial)
    {

        $location = $this->em->getRepository(UnifiLocation::class)->findOneBy([
            'serial' => $serial,
        ]);

        if (is_null($location)) {
            return Http::status(404, 'NOT_A_VALID_LOCATION');
        }

        return Http::status(200, $location->getArrayCopy());
    }

    public function listSites(string $id)
    {

        $controller = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $id,
        ]);

        if (is_null($controller)) {
            return Http::status(404, 'NOT_A_VALID_CONTROLLER');
        }

        $unifi = new _UniFi($controller->username, $controller->password, $controller->hostname, '');

        if ($unifi->login === false) {
            return Http::status(409, 'DETAILS_INCORRECT');
        }

        return Http::status(200, $unifi->listSites());
    }

    public function listSsids(string $id, string $unifiId)
    {

        $controller = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $id,
        ]);

        if (is_null($controller)) {
            return Http::status(404, 'NOT_A_VALID_CONTROLLER');
        }

        $unifi = new _UniFi($controller->username, $controller->password, $controller->hostname, $unifiId);

        if ($unifi->login === false) {
            return Http::status(409, 'DETAILS_INCORRECT');
        }

        return Http::status(200, $unifi->listSsid());
    }

    public function linkSerialWithController(string $serial, array $body, string $controllerId)
    {

        $getLocationBySerial = $this->em->getRepository(UnifiLocation::class)->findOneBy([
            'serial' => $serial,
        ]);

        if (is_null($getLocationBySerial)) {
            $getLocationBySerial = new UnifiLocation($serial);
            $this->em->persist($getLocationBySerial);
            //return Http::status(404, 'COULD_NOT_LOCATE_UNIFI_LOCATION');
        }

        $credentials = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $controllerId,
        ]);

        $unifi = new _UniFi($credentials->username, $credentials->password, $credentials->hostname, $body['unifiId']);

        if (!$unifi->login) {
            return Http::status(409, 'DETAILS_INCORRECT');
        }

        $setPolicy = $unifi->setGuestPolicy();

        if (!$setPolicy) {
            return Http::status(204, 'FAILED_TO_SET_GUEST_POLICY');
        }

        $getLocationBySerial->unifiControllerId = $controllerId;
        $getLocationBySerial->unifiId = $body['unifiId'];
        $getLocationBySerial->timeout = $body['timeout'];
        $getLocationBySerial->multiSite = $body['multiSite'];
        $getLocationBySerial->multiSiteSsid = $body['multiSiteSsid'];

        $this->em->flush();

        $this->syncRequest($serial);

        return Http::status(200, $getLocationBySerial->getArrayCopy());
    }

    public function getController(string $controllerId)
    {
        $controller = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $controllerId,
        ]);

        if (is_null($controller)) {
            return Http::status(204);
        }

        return Http::status(200, $controller->getArrayCopy());
    }

    public function updateController(string $controllerId, array $body)
    {
        $controller = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $controllerId,
        ]);

        if (is_null($controller)) {
            return Http::status(204);
        }

        $controller->hostname = $body['hostname'];
        $controller->username = $body['username'];
        $controller->password = $body['password'];

        $this->em->flush();

        return Http::status(200, $controller->getArrayCopy());
    }

    public function deleteController(string $controllerId)
    {

        $getController = $this->em->getRepository(UnifiController::class)->findOneBy([
            'id' => $controllerId,
        ]);

        if (is_null($getController)) {
            return Http::status(404, 'NOT_A_VALID_CONTROLLER');
        }

        $findControllerList = $this->em->getRepository(UnifiControllerList::class)->findBy([
            'controllerId' => $controllerId,
        ]);

        foreach ($findControllerList as $controllerList) {
            $this->em->remove($controllerList);
        }

        $findSitesThatUseController = $this->em->getRepository(UnifiLocation::class)->findBy([
            'unifiControllerId' => $controllerId,
        ]);

        foreach ($findSitesThatUseController as $site) {
            $site->unifiControllerId = null;
            $site->unifiId = null;
        }

        $this->em->remove($getController);

        $this->em->flush();

        return Http::status(200, $getController->getArrayCopy());
    }

    //Return List of Controller Ids
    public function getUsersControllers(string $orgId)
    {

        $getUserControllers = $this->em->createQueryBuilder()
            ->select('u.id, u.hostname')
            ->from(UnifiController::class, 'u')
            ->join(UnifiControllerList::class, 's', 'WITH', 'u.id = s.controllerId')
            ->where('s.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($getUserControllers)) {
            return Http::status(404, 'NO_CONTROLLERS_FOUND');
        }

        return Http::status(200, $getUserControllers);
    }

    public function syncRequest(string $serial)
    {

        $publisher = new QueueSender();

        $publisher->sendMessage([
            'serial' => $serial,
        ], QueueUrls::UNIFI_SYNC_DEVICES);

        return Http::status(200);
    }

    public function setUpController(Organization $organization, array $body)
    {
        $unifi = new _UniFi($body['username'], $body['password'], $body['hostname'], '');

        if (!$unifi->login) {
            return Http::status(409, 'DETAILS_INCORRECT');
        }

        $controller = $this->em->getRepository(UnifiController::class)->findOneBy([
            'hostname' => $body['hostname'],
            'username' => $body['username'],
            'password' => $body['password'],
        ]);

        if (is_null($controller)) {
            $version = $unifi->version();
            if (is_null($version)) {
                $version = '';
            }
            $controller = new UnifiController(
                $body['hostname'],
                $body['username'],
                $body['password'],
                $version
            );
            $this->em->persist($controller);
        }

        $newUniFiControllerList = new UnifiControllerList($organization);
        $newUniFiControllerList->controllerId = $controller->id;
        $this->em->persist($newUniFiControllerList);

        $this->em->flush();

        return Http::status(200, $controller->getArrayCopy());
    }

    /**
     * @param $serial
     * @return mixed
     */

    public function settings(string $serial)
    {

        $settingsCtrl = new _UniFiSettingsController($this->em);
        $settings = $settingsCtrl->settings($serial);
        if ($settings['status'] === 200) {
            return $settings['message'];
        }

        return [];
    }

    /**
     * @param bool $status
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function getLocations(bool $status = true)
    {
        $results = $this->em->createQueryBuilder()
            ->select('u')
            ->from(UnifiController::class, 'u')
            ->where('u.status = :status')
            ->andWhere('u.hostname IS NOT NULL')
            ->andWhere('u.hostname != :hostname')
            ->setParameter('status', $status)
            ->setParameter('hostname', ' ')
            ->getQuery()
            ->getArrayResult();

        $this->em->flush();

        $send = [];

        if (!empty($results)) {
            $send = $results;
        }

        return $send;
    }
}
