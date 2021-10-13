<?php
/**
 * Created by jamieaitken on 22/01/2019 at 15:45
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\LocationSearch;


use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Type\LocationTypes;
use App\Models\Locations\Type\LocationTypesSerial;
use App\Models\NetworkAccess;

class AccessibleLocationSearcher extends SearchableLocation
{
    public function prepareBaseStatement()
    {
        $sqlStatement = 'ns.serial, ns.alias, na.admin, na.reseller, i.status, lt.id as locationType, ns.type';


        if (!is_null($this->getAddress()) || !is_null($this->getPostCode()) || !is_null($this->getTown())) {
            $sqlStatement .= ',lp.latitude, lp.longitude, lp.formattedAddress,lp.postCode, lp.postalTown';
        }

        $fetchLocations = $this->entityManager->createQueryBuilder()
            ->select($sqlStatement)
            ->from(LocationSettings::class, 'ns')
            ->leftJoin(NetworkAccess::class, 'na', 'WITH', 'ns.serial = na.serial')
            ->leftJoin(LocationTypesSerial::class, 'ls', 'WITH', 'na.serial = ls.serial')
            ->leftJoin(LocationTypes::class, 'lt', 'WITH', 'ls.locationTypeId = lt.id');

        if (!is_null($this->getAddress()) || !is_null($this->getPostCode()) || !is_null($this->getTown())) {
            $fetchLocations = $fetchLocations->leftJoin(LocationPosition::class, 'lp',
                'WITH', 'ns.location = lp.id');
        }

        $fetchLocations = $fetchLocations->leftJoin(Inform::class, 'i', 'WITH',
            'na.serial = i.serial')
            ->where('ns.serial IN (:serials)')
            ->setParameter('serials', $this->getSerials());

        if (!is_null($this->getLocationType())) {
            $fetchLocations = $fetchLocations->andWhere('ns.type = :type')
                ->setParameter('type', $this->getLocationType());
        }

        if (!is_null($this->getBusinessType())) {
            $fetchLocations = $fetchLocations->andWhere('lt.name LIKE :name')
                ->setParameter('name', $this->getBusinessType() . '%');
        }

        if (!is_null($this->getAddress())) {
            $fetchLocations = $fetchLocations->andWhere('lp.formattedAddress LIKE :address')
                ->setParameter('address', $this->getAddress() . '%');
        }

        if (!is_null($this->getPostCode())) {
            $fetchLocations = $fetchLocations->andWhere('lp.postCode LIKE :postCode')
                ->setParameter('postCode', $this->getPostCode() . '%');
        }

        if (!is_null($this->getTown())) {
            $fetchLocations = $fetchLocations->andWhere('lp.postalTown LIKE :postalTown')
                ->setParameter('postalTown', $this->getTown());
        }

        return $fetchLocations;
    }
}