<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 24/05/2017
 * Time: 12:55
 */

namespace App\Controllers\Integrations\IgniteNet;

use App\Controllers\Locations\_LocationsInformController;
use App\Controllers\Schedule\_DeformController;
use App\Models\Locations\Informs\Inform;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _IgniteNetInformController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function igniteNetInform(Request $request, Response $response)
    {
        $serial      = $request->getAttribute('serial');
        $queryParams = $request->getQueryParams();
        $ip          = $request->getHeader('X-Forwarded-For');

        if (isset($queryParams['ip'])) {
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
        } elseif (is_null($ip) || empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $locationInform = new _LocationsInformController($this->em);
        $inform         = $locationInform->createInform($serial, $ip, 'IGNITENET', []);

        $this->em->clear();

        if (is_bool($inform)) {
            return $response->withJson([], 400);
        }

        return $response->withJson([], 200);
    }

    public function igniteNetDeform(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');

        $inform = $this->em->getRepository(Inform::class)->findOneBy([
            'vendor' => 'IGNITENET',
            'status' => true,
            'serial' => $serial
        ]);

        if (is_null($inform)) {
            $this->em->clear();

            return $response->withJson('FAILED_TO_FIND_INFORM', 404);
        }

        $deform = new _DeformController($this->em);
        $deform->persistOffline($inform);

        $this->em->clear();

        return $response->withJson('UNINFORMED', 200);
    }
}
