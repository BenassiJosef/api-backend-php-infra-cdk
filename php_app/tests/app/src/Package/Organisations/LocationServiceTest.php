<?php

declare(strict_types=1);

namespace StampedeTests\app\src\Package\Organisations;

use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Models\LocationAccess;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Social\LocationSocial;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Models\Role;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Package\Organisations\LocationAccessChangeRequest;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\UserRoleChecker;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class LocationServiceTest extends TestCase
{
    private $em;
    private $logger;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->logger = $this->createMock(Logger::class);
        $stampedeOrg = EntityHelpers::createRootOrg($this->em);
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }



    public function testWhoCanAccessLocation()
    {
        $locationService = new LocationService($this->em);
        $adminRole = $this->em->getRepository(Role::class)->findOneBy(['legacyId' => Role::LegacyAdmin]);
        $admin = EntityHelpers::createOauthUser($this->em, "foo@bar.com", "", "male", "");
        $root = EntityHelpers::createOrganisation($this->em, "Bobs Burgers", $admin);
        $child = new Organization("Child Org", $admin, $root);
        $this->em->persist($child);
        $access = new OrganizationAccess($child, $admin, $adminRole);
        $this->em->persist($access);
        $this->em->flush();
        $location = EntityHelpers::createLocationSettings(
            $this->em,
            "FOOBARBAZ",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('FOOBARBAZ'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            "{}",
            $child
        );
        $singleLocationAdmin = EntityHelpers::createOauthUser($this->em, "baz@qux.com", "", "male", "");
        $this->em->persist($singleLocationAdmin);
        $singleLocationAccess = new LocationAccess($location, $singleLocationAdmin, $adminRole);
        $this->em->persist($singleLocationAccess);
        $this->em->flush();
        $midLevelAdmin = EntityHelpers::createOauthUser($this->em, "mid@level.com", "", "", "");
        $this->em->persist($midLevelAdmin);
        $this->em->persist(new OrganizationAccess($child, $midLevelAdmin, $adminRole));
        $this->em->flush();
        $this->em->clear();

        $access = $locationService->whoCanAccessLocation($location);
        $gotEmails = [];
        foreach ($access as $user) {
            $gotEmails[] = $user->getEmail();
        }
        $expectedEmails = ["baz@qux.com", "foo@bar.com", "mid@level.com"];
        self::assertEquals($expectedEmails, $gotEmails);
    }


    public function testUpdateUserLocationAccessSuccess()
    {
        $locationService = new LocationService($this->em);

        $admin   = EntityHelpers::createOauthUser($this->em, "admin@test.com", "password123", "test", "123", "Bob", "Brickhouse", "stripe1");

        $orgService = new OrganizationService($this->em);
        $organisation = $orgService->createOrganizationForOwner($admin);

        $locationSettings1 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial1",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial1'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "schedule",
            "Url",
            "{}",
            $organisation
        );

        $locationSettings2 = EntityHelpers::createLocationSettings(
            $this->em,
            "serial2",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('serial2'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "schedule",
            "Url",
            "{}",
            $organisation
        );

        $subject = EntityHelpers::createOauthUser($this->em, "moderator@test.com", "password456", "test", "123", "Bob", "Brickhouse", "stripe2");
        $role    = $this->em->getRepository(Role::class)->findOneBy(["legacyId" => Role::LegacyModerator]);

        $locationAccessRequest = new LocationAccessChangeRequest($admin, $subject, $role, ['serial1', 'serial2']);

        $locationService->updateUserLocationAccess($locationAccessRequest);
        self::assertTrue(true);
    }

}
