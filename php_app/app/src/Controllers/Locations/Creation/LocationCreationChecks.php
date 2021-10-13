<?php
/**
 * Created by jamieaitken on 28/01/2019 at 14:48
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Creation;


use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Models\NetworkAccess;
use Doctrine\ORM\EntityManager;

class LocationCreationChecks
{
    protected $em;
    private $vendor;
    private $serial;

    private $reasonForFailure = '';

    const REASON_SERIAL_LENGTH = 'SERIAL_LENGTH';
    const REASON_IN_ACTIVE_SUBSCRIPTION = 'SERIAL_IN_ACTIVE_SUBSCRIPTION';
    const REASON_MIKROTIK_NOT_REGISTERED = 'MIKROTIK_NOT_REGISTERED';

    public function __construct(EntityManager $em, ?string $serial, string $vendor)
    {
        $this->em     = $em;
        $this->vendor = $vendor;
        $this->serial = $serial;
    }

    public function executePreCreationChecks()
    {
        $hasPassedAllTests = false;

        if (!$this->isSerialLongEnough()) {
            $this->reasonForFailure = LocationCreationChecks::REASON_SERIAL_LENGTH;

            return $hasPassedAllTests;
        }

        if (!$this->isSerialInActiveSubscription()) {
            $this->reasonForFailure = LocationCreationChecks::REASON_IN_ACTIVE_SUBSCRIPTION;

            return $hasPassedAllTests;
        }

        if ($this->vendor === 'mikrotik') {
            if (!$this->isMikrotikSerialInSystem()) {
                $this->reasonForFailure = LocationCreationChecks::REASON_MIKROTIK_NOT_REGISTERED;

                return $hasPassedAllTests;
            }
        }

        $hasPassedAllTests = true;

        return $hasPassedAllTests;
    }

    private function isSerialInActiveSubscription()
    {
        $isNotInActiveSubscription = true;

        $checkForActiveLocationBasedSubscription = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(Subscriptions::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.status = :active')
            ->andWhere('u.plan_id IN (:locationBasedPlans)')
            ->setParameter('serial', $this->serial)
            ->setParameter('active', 'active')
            ->setParameter('locationBasedPlans', ['all-in', 'all-in_an', 'starter', 'starter_an'])
            ->getQuery()
            ->getArrayResult();
        if (!empty($checkForActiveLocationBasedSubscription)) {
            $isNotInActiveSubscription = false;
        }

        return $isNotInActiveSubscription;
    }

    private function isMikrotikSerialInSystem()
    {
        $doesExist = false;

        $checkSerialExists = $this->em->createQueryBuilder()
            ->select('p.physicalSerial')
            ->from(MikrotikSymlinkSerial::class, 'p')
            ->where('p.physicalSerial = :serial')
            ->orWhere('p.virtualSerial = :serial')
            ->setParameter('serial', $this->serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($checkSerialExists)) {
            $doesExist = true;
        }

        return $doesExist;
    }

    private function isSerialLongEnough()
    {
        $longEnough = true;

        if (strlen($this->serial) !== 12) {
            $longEnough = false;
        }

        return $longEnough;
    }

    public function setSerial(?string $serial)
    {
        $this->serial = $serial;
    }

    public function setVendor(string $vendor)
    {
        $this->vendor = $vendor;
    }

    public function getReasonForFailure()
    {
        return $this->reasonForFailure;
    }
}