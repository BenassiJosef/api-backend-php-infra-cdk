<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/04/2017
 * Time: 10:17
 */

namespace App\Controllers\Schedule;

use App\Controllers\User\_UserController;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Other\LocationOther;
use App\Models\UserData;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ValidationTimeoutsController
{

    protected $em;
    protected $user;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->user = new _UserController($this->em);
    }

    function getRoute(Request $request, Response $response, $args)
    {

        $networkSessions = $this->getSessions();
        foreach ($networkSessions as $serial => $session) {
            foreach ($session as $sesh) {
                $this->user->deauth($sesh['mac'], $serial);
            }
        }

        $this->em->clear();

        return $response->withJson($networkSessions, 200);
    }

    function getNetworks()
    {

        $networks = $this->em->createQueryBuilder()
            ->select('lo.validationTimeout, ns.other, ns.serial, mi.masterSite')
            ->from(LocationSettings::class, 'ns')
            ->leftJoin(LocationOther::class, 'lo', 'WITH', 'lo.id = ns.other')
            ->leftJoin(Inform::class, 'i', 'WITH', 'i.serial = ns.serial')
            ->leftJoin(MikrotikInform::class, 'mi', 'WITH', 'mi.informId = i.id')
            ->where('lo.validationTimeout != 0')
            ->andWhere('lo.validation = :true')
            ->andWhere('i.vendor = :vendor')
            ->andWhere('lo.validationTimeout IS NOT NULL')
            ->andWhere('ns.type = 0 OR ns.type = 2')
            ->setParameter('vendor', 'MIKROTIK')
            ->setParameter('true', true)
            ->getQuery()
            ->getArrayResult();

        if (empty($networks)) {
            return [];
        }

        return $networks;
    }

    function getSessions()
    {
        $now      = new \DateTime();
        $serials  = [];
        $networks = $this->getNetworks();

        foreach ($networks as $network) {
            $serials[] = $network['serial'];
        }

        $sessions = $this->em->createQueryBuilder()
            ->select('u.mac, u.serial, up.id')
            ->from(UserData::class, 'u')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = u.profileId')
            ->leftJoin(LocationSettings::class, 'ns', 'WITH', 'u.serial = ns.serial')
            ->leftJoin(LocationOther::class, 'lo', 'WITH', 'ns.other = lo.id')
            ->where('u.serial IN (:serials)')
            ->andWhere('up.verified = 0')
            ->andWhere('up.email IS NOT NULL')
            ->andWhere('up.email != :empty')
            ->andWhere('TIMESTAMPDIFF(MINUTE, u.timestamp, NOW()) > lo.validationTimeout')
            ->andWhere('u.lastupdate > :twoMinsAgo')
            ->andWhere('up.id IS NOT NULL')
            ->groupBy('u.mac')
            ->setParameter('serials', $serials)
            ->setParameter('twoMinsAgo', $now->modify('-2 minutes'))
            ->setParameter('empty', '')
            ->orderBy('u.serial', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($networks as $network) {
            if (!is_null($network['masterSite'])) {
                foreach ($sessions as $key => $session) {
                    if ($session['serial'] === $network['serial']) {
                        $sessions[$key]['serial'] = $network['masterSite'];
                    }
                }
            }
        }

        $res = [];

        foreach ($sessions as $session) {
            if (!isset($res[$session['serial']])) {
                $res[$session['serial']] = [];
            }
            $res[$session['serial']][] = $session;
        }

        return $res;
    }
}
