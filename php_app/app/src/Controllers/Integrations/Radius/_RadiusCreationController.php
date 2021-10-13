<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/08/2017
 * Time: 11:11
 */

namespace App\Controllers\Integrations\Radius;

use App\Controllers\Locations\_LocationCreationController;
use App\Models\Locations\Informs\Inform;
use App\Models\Radius\RadiusGroupCheck;
use App\Models\RadiusVendor;
use App\Package\Vendors\Information;
use App\Utils\RadiusEngine;
use Doctrine\ORM\EntityManager;

class _RadiusCreationController extends _LocationCreationController
{
    protected $radius;
    /**
     * @var Information $information
     */
    protected $information;

    public function __construct(EntityManager $em)
    {
        $this->information = new Information($em);
        parent::__construct($em);
        $this->radius = RadiusEngine::getInstance();
    }

    public function createInform(?string $serial)
    {
        if (is_null($serial)) {
            $serial = $this->serialGenerator();
        }

        $radiusDevice = new RadiusVendor($serial, null, strtoupper($this->getVendor()));
        $this->em->persist($radiusDevice);
        $this->em->flush();

        $arrayCopy = $radiusDevice->getArrayCopy();
        $vendorInformation = new Information($this->em);
        $newInform = new Inform(
            $serial,
            "0",
            true,
            strtoupper($this->getVendor()),
            $vendorInformation->getFromKey($this->getVendor())
        );
        $this->em->persist($newInform);


        if ($this->getVendor() === 'ruckus' || $this->getVendor() === 'ruckus-smartzone' || $this->getVendor() === 'meraki' || $this->getVendor() === 'engenius') {
            $newGroup = new RadiusGroupCheck($arrayCopy['serial'], $arrayCopy['serial'], 'WISPr-Location-ID');
        } else {
            $newGroup = new RadiusGroupCheck($arrayCopy['serial'], $arrayCopy['serial'], 'NAS-Identifier');
        }

        $this->radius->persist($newGroup);
        $this->radius->flush();

        $this->dataGeneratedWithinInform = [
            'inform'       => $newInform->getArrayCopy(),
            'radiusDevice' => $radiusDevice->getArrayCopy(),
            'newGroup'     => $newGroup->getArrayCopy()
        ];

        $this->serial = $serial;

        return $arrayCopy['serial'];
    }

    public function isRadiusVendor(string $vendor)
    {
        $vendorInfo = $this->information->getFromKey($vendor);
        if (is_null($vendorInfo)) {
            return false;
        }
        return $vendorInfo->getRadius();
    }

    public function createBespokeLogic(string $serial, string $vendor)
    {

        $radiusDevice = new RadiusVendor($serial, null, strtoupper($vendor));
        $this->em->persist($radiusDevice);

        if ($vendor === 'ruckus' || $vendor === 'ruckus-smartzone' || $vendor === 'meraki') {
            $newGroup = new RadiusGroupCheck($serial, $serial, 'WISPr-Location-ID');
        } else {
            $newGroup = new RadiusGroupCheck($serial, $serial, 'NAS-Identifier');
        }

        $this->radius->persist($newGroup);
        $this->radius->flush();
        $this->em->flush();
    }

    public function deleteBespokeLogic(string $serial, string $vendor)
    {
        $this->em->createQueryBuilder()
            ->delete(RadiusVendor::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        $this->radius->createQueryBuilder($this->em)
            ->delete(RadiusGroupCheck::class, 'u')
            ->where('u.groupname = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();
    }

    public function isLocationBeingReactivated(string $serial)
    {
        $reactivated = false;

        $query = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(RadiusVendor::class, 'u')
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
