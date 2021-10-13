<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 06/12/2016
 * Time: 00:34
 */

namespace App\Controllers\Locations;

use App\Controllers\Locations\LocationSearch\LocationSearchFactory;
use App\Controllers\Locations\Reports\_RegistrationReportController;
use App\Models;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Type\LocationTypesSerial;
use App\Models\Locations\Type\LocationTypes;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Class _LocationsController
 * @package App\Controllers\Locations
 *
 */
class _LocationsController
{

    protected $em;
    protected $connectCache;
    protected $user;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function getLocationRoute(Request $request, Response $response, $args)
    {
        $serial = $request->getAttribute('serial');
        $res    = $this->getLocation($serial);

        $this->em->clear();

        return $response->withJson($res);
    }

    public function getLocation($serial)
    {
        $location = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($location)) {
            return Http::status(404, 'SERIAL_NOT_FOUND');
        }

        $locationArray = $location->getArrayCopy();

        return Http::status(200, $locationArray['alias']);

    }

    public function getLocationQuestionsRoute(Request $request, Response $response)
    {

        $send = $this->getLocationQuestions($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);

    }

    public function getLocationQuestions(string $serial)
    {
        $newRegistrationsReportController = new _RegistrationReportController($this->em);
        $getQuestions                     = $newRegistrationsReportController->fetchHeadersForSerial($serial);

        if (!is_array($getQuestions)) {
            return Http::status(400, 'NOT_A_VALID_LOCATION');
        }

        return Http::status(200, $getQuestions);
    }

    public function deleteLocation(string $serial, string $admin)
    {
        $subscription = $this->em->getRepository(Models\Subscriptions::class)->findOneBy(
            [
                'serial' => $serial
            ]
        );

        if (!empty($subscription)) {
            $s = $subscription->status;
            if ($s === 1 || $s === 'active') {
                return false;
            }
        } else {
            return false;
        }

        $deleteAccess = $this->em->createQueryBuilder()->delete(Models\NetworkAccess::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();


        $deleteAlerts = $this->em->createQueryBuilder()->delete(Models\EmailAlerts::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();


        $deleteSettings = $this->em->createQueryBuilder()->delete(Models\Locations\LocationSettings::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();


        $deleteSubscription = $this->em->createQueryBuilder()->delete(Models\Subscriptions::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();


        $deleteNodes = $this->em->createQueryBuilder()->delete(Models\NodeDetails::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();


        $deleteStatus = $this->em->createQueryBuilder()->delete(Models\NetworkStatus::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();

        $deleteUniFi = $this->em->createQueryBuilder()->delete(Models\UnifiController::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();

        $deleteOpenMesh = $this->em->createQueryBuilder()->delete(Models\RadiusVendor::class, 'n')
            ->where('n.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery();

        $alerts       = $deleteAlerts->execute();
        $settings     = $deleteSettings->execute();
        $subscription = $deleteSubscription->execute();
        $nodes        = $deleteNodes->execute();
        $status       = $deleteStatus->execute();
        $access       = $deleteAccess->execute();
        $unifi        = $deleteUniFi->execute();
        $openmesh     = $deleteOpenMesh->execute();

        $event = new Models\EventLog();

        $event->serial    = $serial;
        $event->code      = 9;
        $event->timestamp = new \DateTime('now');
        $event->admin     = $admin;
        $event->message   = 'LOCATION DELETED';

        $this->em->persist($event);
        $this->em->flush();

        return [
            'subscription' => $subscription,
            'alerts'       => $alerts,
            'settings'     => $settings,
            'access'       => $access,
            'nodes'        => $nodes,
            'status'       => $status,
            'unifi'        => $unifi,
            'openmesh'     => $openmesh
        ];
    }

    public function delete(Request $request, Response $response, $args)
    {

        $serial = $request->getAttribute('serial');
        $user   = $request->getAttribute('user');
        $uid    = $user['uid'];

        $this->em->clear();

        return $response->write(
            json_encode($this->deleteLocation($serial, $uid))
        );
    }

    public function create(Request $request, Response $response, $args)
    {
    }

    public function fetchLocationsThatUserHasAccessToRoute(Request $request, Response $response)
    {
        $currentUser = $request->getAttribute('accessUser');

        $params = $request->getQueryParams();

        $newLocationSearchFactory = new LocationSearchFactory(
            $request->getAttribute('context'),
            $params,
            $currentUser,
            $this->em);
        $send                     = $newLocationSearchFactory->createInstance();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }
}
