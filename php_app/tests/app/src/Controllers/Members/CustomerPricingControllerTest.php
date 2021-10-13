<?php
/**
 * Created by chrisgreening on 05/03/2020 at 14:09
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace StampedeTests\app\src\Controllers\Members;

use App\Controllers\Members\CustomerPricingController;
use App\Models\Members\CustomerPricing;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class CustomerPricingControllerTest extends TestCase
{
    private $em;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testGetRoute()
    {
        $owner = EntityHelpers::createOauthUser($this->em, "test@test.com", "password123");
        $organisation = EntityHelpers::createOrganisation($this->em, "Test Org", $owner);

        $pricing = new CustomerPricing($organisation);
        $this->em->persist($pricing);
        $this->em->flush();
        $this->em->clear();

        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->willReturn($organisation->getId()->toString());
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('withJson')->with(
            $this->callback(function ($result) {
                self::assertEquals(200, $result["status"]);
                return true;
            }));
        $organisationService = new OrganizationService($this->em);

        $controller = new CustomerPricingController($this->em, $organisationService);
        $controller->getRoute($request, $response);
    }

    public function testUpdateRoute()
    {
        $owner = EntityHelpers::createOauthUser($this->em, "test@test.com", "password123");
        $organisation = EntityHelpers::createOrganisation($this->em, "Test Org", $owner);

        $pricing = new CustomerPricing($organisation);
        $this->em->persist($pricing);
        $this->em->flush();
        $this->em->clear();

        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->willReturn($organisation->getId()->toString());
        $request->method('getParsedBody')->willReturn([
            "lite" => 999
        ]);
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('withJson')->with(
            $this->callback(function ($result) {
                self::assertEquals(200, $result["status"]);
                self::assertEquals(999, $result["message"]["lite"]);
                return true;
            }));
        $organisationService = new OrganizationService($this->em);

        $controller = new CustomerPricingController($this->em, $organisationService);
        $controller->updateRoute($request, $response);
    }
}
