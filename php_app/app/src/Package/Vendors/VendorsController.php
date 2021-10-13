<?php

namespace App\Package\Vendors;

use App\Models\Locations\Informs\Inform;
use App\Models\Locations\Vendors;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class VendorsController
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
        $this->information = new Information($this->entityManager);
    }

    public function getVendors(Request $request, Response $response): Response
    {
        /**
         * @var Vendors[] $vendors
         */
        $vendors = $this->entityManager->getRepository(Vendors::class)->findAll();

        $res = [];
        foreach ($vendors as $vendor) {
            $res[] = $vendor->jsonSerialize();
        }
        return $response->withJson($res, 200);
    }

    public function changeVendor(Request $request, Response $response): Response
    {
        $vendorId = $request->getQueryParam('vendor_id', null);
        $serial = $request->getAttribute('serial', null);
        if (is_null($vendorId)) {
            return $response->withJson('NO_VENDOR', 404);
        }
        if (is_null($serial)) {
            return $response->withJson('NO_SERIAL', 404);
        }
        /**
         * @var Vendors $vendors
         */
        $vendor = $this->entityManager->getRepository(Vendors::class)->find($vendorId);

        if (is_null($vendor)) {
            return $response->withJson('INVALID_VENDOR_KEY', 404);
        }

        $inform = $this->information->getFromSerial($serial);
        if (is_null($inform)) {
            return $response->withJson('NO_INFORM_FOUND', 404);
        }

        $inform->setVendorSource($vendor);
        $this->entityManager->persist($inform);
        $this->entityManager->flush();

        return $response->withJson($inform->jsonSerialize(), 200);
    }
}
