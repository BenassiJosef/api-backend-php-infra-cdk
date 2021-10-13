<?php

namespace App\Package\Vendors;

use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Vendors;
use Doctrine\ORM\EntityManager;

class Information
{

    /** 
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager

    ) {
        $this->entityManager = $entityManager;
    }

    public function getFromKey(string $key): Vendors
    {
        /**
         * Handle all the daft legacy formats we might get
         */
        $key = strtolower(str_replace('_', '-', $key));

        /**
         * @var Vendors $vendor
         */
        return $this->entityManager->getRepository(Vendors::class)->findOneBy(['key' => $key]);
    }

    public function getFromSerial(string $serial): ?Inform
    {
        /**
         * @var Inform $inform
         */
        return $this
            ->entityManager
            ->getRepository(Inform::class)
            ->findOneBy(
                [
                    'serial' => $serial
                ]
            );
    }
}
