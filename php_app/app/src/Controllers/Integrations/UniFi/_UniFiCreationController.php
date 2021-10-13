<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 01/02/2017
 * Time: 16:35
 */

namespace App\Controllers\Integrations\UniFi;

use App\Controllers\Locations\_LocationCreationController;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\Locations\Informs\Inform;
use App\Package\Vendors\Information;
use Doctrine\ORM\EntityManager;

class _UniFiCreationController extends _LocationCreationController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function createInform(?string $serial)
    {
        if (is_null($serial)) {
            $serial = parent::serialGenerator();
        }

        $unifi = new UnifiLocation($serial);

        $this->em->persist($unifi);
        $vendorInformation = new Information($this->em);
        $inform = new Inform($serial, 0, true, 'UNIFI', $vendorInformation->getFromKey('unifi'));

        $this->em->persist($inform);
        $this->em->flush();

        $this->serial = $serial;

        $this->dataGeneratedWithinInform = [
            'inform'        => $inform->getArrayCopy(),
            'unifiLocation' => $unifi->getArrayCopy()
        ];

        return $serial;
    }

    public function createBespokeLogic(string $serial, string $vendor)
    {
        $unifi = new UnifiLocation($serial);
        $this->em->persist($unifi);
        $this->em->flush();
    }

    public function deleteBespokeLogic(string $serial, string $vendor)
    {
        $this->em->createQueryBuilder()
            ->delete(UnifiLocation::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
    }

    public function isLocationBeingReactivated(string $serial)
    {
        $reactivated = false;

        $query = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(UnifiLocation::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($query)) {
            $reactivated = true;
        }

        return $reactivated;
    }
}
