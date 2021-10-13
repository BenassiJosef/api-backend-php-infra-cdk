<?php

namespace App\Package\Vendors;

use App\Models\Locations\Informs\Inform as InformsInform;
use App\Models\Radius\RadiusGroupCheck;
use App\Utils\RadiusEngine;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;

class Inform
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Information $information
     */
    private $information;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->radius = RadiusEngine::getInstance();
        $this->information = new Information($this->entityManager);
    }

    public function create(?string $serial, string $vendor): ?string
    {
        if (is_null($serial)) {
            $serial = $this->serialGenerator();
        }
        $inform = $this->information->getFromSerial($serial);
        if (!is_null($inform)) {
            return null;
        }
        $vendorSource = $this->information->getFromKey($vendor);
        $newInform = new InformsInform(
            $serial,
            "0",
            true,
            $vendorSource->getKey(),
            $vendorSource
        );
        $this->entityManager->persist($newInform);

        if ($vendorSource->getRadius()) {
            $group1 = new RadiusGroupCheck($serial, $serial, 'WISPr-Location-ID');
            $group2 = new RadiusGroupCheck($serial, $serial, 'NAS-Identifier');
            $this->radius->persist($group1);
            $this->radius->persist($group2);
            $this->radius->flush();
        }
        $this->entityManager->flush();
        return $serial;
    }

    /**
     * @return string
     */

    public function serialGenerator()
    {
        $serial = strtoupper(Strings::random(12));
        return $serial;
    }
}
