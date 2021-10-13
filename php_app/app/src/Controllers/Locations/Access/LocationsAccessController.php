<?php

/**
 * Created by jamieaitken on 28/01/2019 at 14:55
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Access;


use App\Models\Locations\LocationSettings;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use Doctrine\ORM\EntityManager;
use App\Models\Organization;

class LocationsAccessController
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function initialiseLocationAccess(string $serial)
    {
        $access = new NetworkAccess($serial);

        $this->em->persist($access);

        $this->em->flush();
    }

    public function assignAccess(string $serial, string $admin, string $reseller, Organization $organization)
    {
        /*
        $this->em->createQueryBuilder()
            ->update(NetworkAccess::class, 'u')
            ->set('u.admin', ':admin')
            ->set('u.reseller', ':reseller')
            ->where('u.serial = :serial')
            ->setParameter('admin', $admin)
            ->setParameter('reseller', $reseller)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
*/

        /** @var LocationSettings $location */
        $location = $this
            ->em
            ->getRepository(LocationSettings::class)
            ->findOneBy(['serial' => $serial]);

        $location->setOrganization($organization);
        $this->em->flush();

        return true;
    }

    public function deassignAccess(string $serial)
    {

        $getAccess = $this->em->getRepository(NetworkAccess::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($getAccess)) {
            return false;
        }

        $this->em->createQueryBuilder()
            ->update(NetworkAccess::class, 'u')
            ->set('u.lastRegisteredAdmin', ':lastAdmin')
            ->set('u.admin', ':admin')
            ->where('u.serial = :serial')
            ->setParameter('lastAdmin', $getAccess->admin)
            ->setParameter('admin', null)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(NetworkAccessMembers::class, 'p')
            ->where('p.memberKey IN (:todeleteKey)')
            ->setParameter('todeleteKey', $getAccess->memberKey)
            ->getQuery()
            ->execute();

        return true;
    }
}
