<?php

/**
 * Created by jamieaitken on 29/01/2019 at 13:45
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Mikrotik;


use App\Controllers\Locations\_LocationCreationController;
use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Informs\MikrotikInform;
use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Package\Vendors\Information;
use Doctrine\ORM\EntityManager;

class MikrotikCreationController extends _LocationCreationController
{

    private $ip;
    private $cpuStatus;
    private $model;
    private $osVersion;

    public function __construct(EntityManager $em, ?string $ip, ?int $cpuStatus, ?string $model, ?string $osVersion)
    {
        parent::__construct($em);
        $this->ip        = $ip;
        $this->cpuStatus = $cpuStatus;
        $this->model     = $model;
        $this->osVersion = $osVersion;
    }

    public function createInform(?string $serial)
    {
        $symlinkSerial = new MikrotikSymlinkSerial($serial);
        $this->em->persist($symlinkSerial);
        $vendorInformation = new Information($this->em);

        $inform = new Inform(
            $symlinkSerial->virtualSerial,
            $this->ip,
            true,
            'MIKROTIK',
            $vendorInformation->getFromKey('mikrotik')
        );

        $this->em->persist($inform);

        $mtik = new MikrotikInform(
            $inform->id,
            $this->cpuStatus,
            false,
            null,
            null,
            $this->model,
            false,
            $this->osVersion
        );

        $this->em->persist($mtik);

        $this->em->flush();

        $this->dataGeneratedWithinInform = [
            'inform'         => $inform->getArrayCopy(),
            'mikrotikInform' => $mtik->getArrayCopy()
        ];

        $this->serial = $symlinkSerial->virtualSerial;

        return $symlinkSerial->virtualSerial;
    }

    public function createBespokeLogic(string $serial, string $vendor)
    {
        $symlink                = new MikrotikSymlinkSerial($serial);
        $symlink->virtualSerial = $serial;

        $this->em->persist($symlink);

        $getInformId = $this->getInformId($serial);

        $mikrotikInform = new MikrotikInform($getInformId, null, false, null, null, null, false, null);
        $this->em->persist($mikrotikInform);

        $this->em->flush();
    }

    public function deleteBespokeLogic(string $serial, string $vendor)
    {

        $getInformId = $this->getInformId($serial);

        $this->em->createQueryBuilder()
            ->delete(MikrotikInform::class, 'u')
            ->where('u.informId = :id')
            ->setParameter('id', $getInformId)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(MikrotikSymlinkSerial::class, 'u')
            ->where('u.virtualSerial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
    }

    private function getInformId(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(Inform::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult()[0]['id'];
    }

    public function isLocationBeingReactivated(string $serial)
    {
        $reactivated = false;

        $query = $this->em->createQueryBuilder()
            ->select('u.virtualSerial')
            ->from(MikrotikSymlinkSerial::class, 'u')
            ->where('u.virtualSerial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($query)) {
            $reactivated = true;
        }

        return $reactivated;
    }
}
