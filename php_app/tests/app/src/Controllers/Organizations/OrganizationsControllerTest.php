<?php

declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Organizations;

use App\Controllers\Auth\_PasswordController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Controllers\Members\MemberValidationController;
use App\Controllers\Nearly\EmailValidator;
use App\Controllers\Organizations\OrganizationsController;
use App\Models\Locations\Social\LocationSocial;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Models\Role;
use App\Package\Member\MemberService;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationService;
use App\Package\RequestUser\UserProvider;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class OrganizationsControllerTest extends TestCase
{
    private $em;
    private $org;
    private $org2;
    private $org3;
    private $logger;
    private $userProvider;
    private $organisationService;
    private $controller;
    private $request;
    private $response;
    private $moderator;
    private $memberService;
    /**
     * @var OauthUser
     */
    private $owner;
    /**
     * @var OauthUser
     */
    private $moderator2;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->owner = EntityHelpers::createOauthUser(
            $this->em,
            "admin@admin.com",
            "password123",
            "",
            "reseller1",
            null,
            null,
            null
        );

        $stampedeOrg = EntityHelpers::createRootOrg($this->em);

        $this->org          = EntityHelpers::createOrganisation($this->em, "Test Org", $this->owner);
        $this->org2         = EntityHelpers::createOrganisation($this->em, "Test Org2", $this->owner);
        $this->org3         = EntityHelpers::createOrganisation($this->em, "Test Org3", $this->owner);
        $this->logger       = $this->createMock(Logger::class);
        $this->userProvider = $this->createMock(UserProvider::class);

        $this->organisationService = new OrganizationService($this->em);
        $this->memberService       = new MemberService(
            $this->em,
            new MemberValidationController($this->em),
            new _PasswordController($this->em),
            $this->organisationService
        );
        $this->controller          = new OrganizationsController(
            $this->logger,
            $this->organisationService,
            $this->userProvider,
            $this->em,
            new LocationService($this->em),
            $this->memberService
        );
        $this->request             = $this->createMock(Request::class);
        $this->response            = $this->createMock(Response::class);

        $this->moderator = EntityHelpers::createOauthUser(
            $this->em,
            "moderately@awful.com",
            "password123",
            "",
            "reseller1",
            null,
            null,
            null
        );

        $this->moderator2 = EntityHelpers::createOauthUser(
            $this->em,
            "really@awful.com",
            "password123",
            "",
            "reseller1",
            null,
            null,
            null
        );
        /** @var Role $moderatorRole */
        $moderatorRole = $this->em->getRepository(Role::class)->findOneBy(["legacyId" => Role::LegacyModerator]);
        $this->em->persist(new OrganizationAccess($this->org, $this->moderator, $moderatorRole));
        $this->em->persist(new OrganizationAccess($this->org3, $this->moderator2, $moderatorRole));
        $this->em->flush();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testGetOragnisationWithOwnerRoute(): void
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertEquals("Test Org", $message['message']['name']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getOrganisationRoute($this->request, $this->response);
    }

    public function testGetOrganisationByRole(): void
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->moderator);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->response = $this->createMock(Response::class);
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertEquals("Test Org", $message['message']['name']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getOrganisationRoute($this->request, $this->response);
    }

    public function testUpdateOrganisationNameWithOwnerRoute(): void
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('name')->willReturn("new name");
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertEquals("new name", $message['message']['name']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->updateOrganisationRoute($this->request, $this->response);
    }

    public function testUpdateOrganisationNameNotAllowedRoute(): void
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->moderator);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('name')->willReturn("new name");
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(403, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->updateOrganisationRoute($this->request, $this->response);
    }

    public function testSetParentRoute(): void
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('parentId')->willReturn($this->org2->getId());
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertEquals($this->org2->jsonSerialize(), $message['message']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->setParentRoute($this->request, $this->response);
    }

    public function testGetChildrenRoute()
    {
        $this->org->setChildren(
            [
                $this->org2,
                $this->org3
            ]
        );
        $this->em->persist($this->org);
        $this->em->flush();
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertCount(2, $message['message']);
                    $this->assertEquals("Test Org2", $message['message'][0]['name']);
                    $this->assertEquals("Test Org3", $message['message'][1]['name']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getChildrenRoute($this->request, $this->response);
    }

    public function testSetChildrenRoute()
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('childIds')->willReturn(
            [
                $this->org2->getId()->toString(),
                $this->org3->getId()->toString()
            ]
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->setChildrenRoute($this->request, $this->response);
        $this->em->clear();
        /** @var Organization $theOrg */
        $theOrg = $this->em->getRepository(Organization::class)->find($this->org->getId()->toString());
        $this->assertCount(2, $theOrg->getChildren());
        $this->assertEquals("Test Org2", $theOrg->getChildren()[0]->getName());
        $this->assertEquals("Test Org3", $theOrg->getChildren()[1]->getName());
    }

    public function testAddLocationRoute()
    {

        $location1 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial1",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial1'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            "{}",
            $this->org
        );
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org2->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('serial')->willReturn(
            $location1->getSerial()
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->addLocationRoute($this->request, $this->response);
        $this->em->clear();
        /** @var Organization $theOrg */
        $theOrg = $this->em->getRepository(Organization::class)->find($this->org2->getId()->toString());
        $this->assertCount(1, $theOrg->getLocations());
        $this->assertEquals("serial1", $theOrg->getLocations()[0]->getSerial());
    }

    public function testSetLocationsRoute()
    {
        $location1 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial1",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial1'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        $location2 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial2",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial2'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        $location3 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial3",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial3'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org2->getId()->toString());
        $this->request->method('getParsedBodyParam')->with('serials')->willReturn(
            [
                $location1->getSerial(),
                $location2->getSerial(),
                $location3->getSerial()
            ]
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->setLocationsRoute($this->request, $this->response);
        $this->em->clear();
        /** @var Organization $theOrg */
        $theOrg = $this->em->getRepository(Organization::class)->find($this->org2->getId()->toString());
        $this->assertCount(3, $theOrg->getLocations());
        $this->assertEquals("serial1", $theOrg->getLocations()[0]->getSerial());
        $this->assertEquals("serial2", $theOrg->getLocations()[1]->getSerial());
        $this->assertEquals("serial3", $theOrg->getLocations()[2]->getSerial());
    }

    public function testGetLocationsRoute()
    {
        EntityHelpers::createLocationSettings(
            $this->em,
            "serial1",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial1'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        EntityHelpers::createLocationSettings(
            $this->em,
            "serial2",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial2'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        EntityHelpers::createLocationSettings(
            $this->em,
            "serial3",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial3'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            [],
            $this->org
        );
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertCount(3, $message['message']);
                    $this->assertEquals("serial1", $message['message'][0]['serial']);
                    $this->assertEquals("serial2", $message['message'][1]['serial']);
                    $this->assertEquals("serial3", $message['message'][2]['serial']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getLocationsRoute($this->request, $this->response);
    }

    public function testGetUsersRoute()
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getQueryParam')->willReturn(false);
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertCount(1, $message['message']);
                    $this->assertEquals("moderately@awful.com", $message['message'][0]['email']);
                    $this->assertEquals("Moderator", $message['message'][0]['role']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getUsersRoute($this->request, $this->response);
    }

    public function testGetUsersRouteDeep()
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org->getId()->toString());
        $this->request->method('getQueryParam')->willReturn(true);
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);
                    $this->assertCount(2, $message['message']);
                    $this->assertEquals("moderately@awful.com", $message['message'][0]['email']);
                    $this->assertEquals("Moderator", $message['message'][0]['role']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->getAllUsersRoute($this->request, $this->response);
    }

    public function testAddUserRoute()
    {
        $newUser = EntityHelpers::createOauthUser(
            $this->em,
            "ace@red_dward.com",
            "password123",
            "",
            "reseller1",
            "Ace",
            "Rimmer",
            null
        );

        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->willReturn($this->org2->getId()->toString());
        $this->request->method('getParsedBodyParam')->willReturnOnConsecutiveCalls(
            /* uid */
            $newUser->getUid(), /* email */
            null, /* role */
            Role::LegacyModerator,
            /* uid */
            null, /* email */
            'ace@red_dward.com', /* role */
            Role::LegacyReports
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->addUserRoute($this->request, $this->response);
        $this->em->clear();
        /** @var OrganizationAccess $access */
        $access = $this->em->getRepository(OrganizationAccess::class)->findOneBy(["organizationId" => $this->org2->getId()]);
        self::assertNotNull($access);
        $this->assertEquals("Moderator", $access->getRole()->getName());

        /** @var OrganizationAccess $access */
        $access = $this->em->getRepository(OrganizationAccess::class)->findOneBy(["userId" => $newUser->getUid()]);
        self::assertNotNull($access);
        self::assertEquals("Moderator", $access->getRole()->getName());

        // add the same user with a different role
        $this->controller->addUserRoute($this->request, $this->response);
        /** @var OrganizationAccess[] $access */
        $access = $this->em->getRepository(OrganizationAccess::class)->findBy(["userId" => $newUser->getUid()]);
        self::assertCount(1, $access);
        self::assertEquals("Reports", $access[0]->getRole()->getName());
    }


    public function testRemoveUserRoute()
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->will(
            $this->returnValueMap(
                [
                    [
                        "orgId",
                        null,
                        $this->org->getId()->toString()
                    ],
                    [
                        "uid",
                        null,
                        $this->moderator->getUid()
                    ]
                ]
            )
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->removeUserRoute($this->request, $this->response);
        $this->em->clear();
        /** @var Organization $theOrg */
        $theOrg    = $this->em->getRepository(Organization::class)->find($this->org->getId()->toString());
        $orgAccess = $this->em->getRepository(OrganizationAccess::class)->findOneBy(["userId" => $this->moderator->getUid()]);
        self::assertNull($orgAccess);
    }

    public function testAddNewChildRoute()
    {
        $this->userProvider->method('getOauthUser')->willReturn($this->owner);
        $this->request->method('getAttribute')->will(
            $this->returnValueMap(
                [
                    [
                        "orgId",
                        null,
                        $this->org->getId()->toString()
                    ]
                ]
            )
        );
        $this->request->method('getParsedBodyParam')->will(
            $this->returnValueMap(
                [
                    [
                        "name",
                        null,
                        "New Org"
                    ]
                ]
            )
        );
        $this->response->method('withJson')->with(
            $this->callback(
                function ($message) {
                    $this->assertEquals(200, $message['status']);

                    return true;
                }
            )
        )->willReturn($this->response);
        $this->controller->addNewChildRoute($this->request, $this->response);
        $this->em->clear();
        /** @var Organization $theOrg */
        $theOrg = $this->em->getRepository(Organization::class)->find($this->org->getId()->toString());
        $this->assertCount(1, $theOrg->getChildren());
        self::assertEquals("New Org", $theOrg->getChildren()[0]->getName());
    }
}
