<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 23/05/2017
 * Time: 10:13
 */

namespace App\Controllers\Integrations\IgniteNet;

use App\Controllers\Locations\_LocationCreationController;
use App\Models\Locations\Informs\Inform;
use App\Package\Vendors\Information;
use Doctrine\ORM\EntityManager;

class _IgniteNetCreationController extends _LocationCreationController
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
        $vendorInformation = new Information($this->em);

        $inform = new Inform($serial, 0, true, 'IGNITENET', $vendorInformation->getFromKey('ignitenet'));

        $this->em->persist($inform);
        $this->em->flush();

        $this->serial = $serial;

        $this->dataGeneratedWithinInform = [
            'inform' => $inform->getArrayCopy()
        ];

        return $serial;
    }

    public function createBespokeLogic(string $serial, string $vendor)
    {
    }

    public function deleteBespokeLogic(string $serial, string $vendor)
    {
    }

    public function isLocationBeingReactivated(string $serial)
    {
        $reactivated = false;

        $query = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(Inform::class, 'u')
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
