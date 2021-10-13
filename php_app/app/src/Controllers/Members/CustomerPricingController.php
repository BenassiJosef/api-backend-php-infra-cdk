<?php
/**
 * Created by jamieaitken on 12/06/2018 at 10:58
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Members;

use App\Models\Members\CustomerPricing;
use App\Package\Organisations\OrganisationNotFoundException;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use Cassandra\Custom;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class CustomerPricingController
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var OrganizationService
     */
    private $organizationService;

    /**
     * CustomerPricingController constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->organizationService = new OrganizationService($em);
    }

    public function getRoute(Request $request, Response $response)
    {
        try {
            $send = $this->getPricing($request->getAttribute('orgId'));

            return $response->withJson($send, $send['status']);
        } catch (OrganisationNotFoundException $ex) {
            return $response->withStatus(404, $ex->getMessage());
        }
    }

    public function updateRoute(Request $request, Response $response)
    {
        try {

            $send = $this->update($request->getAttribute('orgId'), $request->getParsedBody());

            $this->em->clear();

            return $response->withJson($send, $send['status']);
        } catch (OrganisationNotFoundException $ex) {
            return $response->withStatus(404, $ex->getMessage());
        }
    }

    public function getPricing(string $organizationId)
    {
        $pricing = $this->findOrCreatePricing($organizationId);
        return Http::status(200, $pricing->getArrayCopy());
    }

    private function update(string $organizationId, array $body)
    {
        $pricing             = $this->findOrCreatePricing($organizationId);
        $allowedKeysToUpdate = array_keys($pricing->getArrayCopy());

        foreach ($body as $key => $value) {
            if (in_array($key, $allowedKeysToUpdate)) {
                $pricing->$key = $value;
            }
        }

        $this->em->persist($pricing);
        $this->em->flush();

        return Http::status(200, $pricing->getArrayCopy());
    }

    public function findOrCreatePricing(string $organizationId)
    {
        $organization = $this
            ->organizationService
            ->getOrganisationById($organizationId);
        $pricing = $this
            ->em
            ->getRepository(CustomerPricing::class)
            ->findOneBy(
                [
                    "organizationId" => $organizationId,
                ]
            );

        if ($pricing === null) {
            if ($organization === null) {
                throw new OrganisationNotFoundException("Organisation not found");
            }
            $pricing = new CustomerPricing($organization);
            $this->em->persist($pricing);
            $this->em->flush();
        }
        return $pricing;
    }
}