<?php

namespace StampedeTests\app\src\Package\Organisations;

use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Models\LocationAccess;
use App\Models\Locations\Social\LocationSocial;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;
use App\Models\Role;

class UserRoleCheckerTest extends TestCase
{

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Organization
     */
    private $root;
    /**
     * @var OauthUser
     */
    private $owner;
    /**
     * @var OauthUser
     */
    private $admin;
    /**
     * @var OauthUser
     */
    private $sameOrgUser;
    /**
     * @var OauthUser
     */
    private $subject2;
    /**
     * @var OauthUser
     */
    private $outsideUser;
    /**
     * @var OauthUser
     */
    private $locationAccessUser;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();

        $this->owner = EntityHelpers::createOauthUser($this->em, "owner@banana.com", "password1", "", "aaa");
        $this->admin = EntityHelpers::createOauthUser($this->em, "admin@admin.com", "hunter2", "", "");

        $this->sameOrgUser = EntityHelpers::createOauthUser($this->em, "subject1@org.com", "hunter2", "", "");
        $this->subject2    = EntityHelpers::createOauthUser($this->em, "subject1@location.com", "hunter2", "", "");
        $this->outsideUser = EntityHelpers::createOauthUser($this->em, "no_access@somewhere.com", "hunter2", "", "");

        /** @var Role $adminRole */
        $adminRole = $this->em->getRepository(Role::class)->findOneBy(['legacyId' => Role::LegacyAdmin]);
        // create the org
        $this->root = EntityHelpers::createOrganisation($this->em, "Org1", $this->owner);
        $this->root->setType(Organization::RootType);
        // add the admin user
        $this->em->persist(new OrganizationAccess($this->root, $this->admin, $adminRole));

        // add another user to the org
        $this->em->persist(new OrganizationAccess($this->root, $this->sameOrgUser, $adminRole));

        $this->em->persist($this->root);

        $locationSettings         = EntityHelpers::createLocationSettings(
            $this->em,
            "bob",
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi('bob'),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            "",
            "",
            "{}",
            $this->root
        );
        $this->locationAccessUser = EntityHelpers::createOauthUser($this->em, "location@access.com", "hunter2", "", "");
        $this->em->persist(new LocationAccess($locationSettings, $this->locationAccessUser, $adminRole));


        // create some child orgs
        $otherUser = EntityHelpers::createOauthUser($this->em, "admin@admin.com", "hunter2", "", "");
        $reseller = EntityHelpers::createOrganisation($this->em, "Reseller", $otherUser);
        $reseller->setType(Organization::ResellerType);
        $this->em->persist($reseller);
        $child1 = EntityHelpers::createOrganisation($this->em, "Child 1", $otherUser);
        $child2 = EntityHelpers::createOrganisation($this->em, "Child 2", $otherUser);
        $child3 = EntityHelpers::createOrganisation($this->em, "Child 3", $otherUser);
        $this->root->addChild($child1);
        $child1->addChild($child2);
        $child2->addChild($child3);

        $this->root->setType(Organization::RootType);


        $this->em->flush();
        $this->em->clear();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testHasAccessToOrganizationType()
    {
        $userRoleChecker = new UserRoleChecker($this->em);
        $hasAccess = $userRoleChecker->hasAccessToOrganizationType(
            $this->owner,
            [Organization::ResellerType, Organization::RootType],
            [Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller]
        );
        self::assertTrue($hasAccess);
    }


    public function testHasAccessToPersonInSameOrg()
    {
        $userRoleChecker = new UserRoleChecker($this->em);
        self::assertTrue($userRoleChecker->hasAdminAccessToUser($this->owner, $this->sameOrgUser->getUid()));
        self::assertTrue($userRoleChecker->hasAdminAccessToUser($this->admin, $this->sameOrgUser->getUid()));
        self::assertFalse($userRoleChecker->hasAdminAccessToUser($this->owner, $this->outsideUser->getUid()));
        self::assertFalse($userRoleChecker->hasAdminAccessToUser($this->admin, $this->outsideUser->getUid()));
    }

    public function testHasAccessToPersonInMyLocations()
    {
        $userRoleChecker = new UserRoleChecker($this->em);
        self::assertTrue($userRoleChecker->hasAdminAccessToUser($this->owner, $this->locationAccessUser->getUid()));
        self::assertTrue($userRoleChecker->hasAdminAccessToUser($this->admin, $this->locationAccessUser->getUid()));
    }

    public function testGetOrganisations()
    {
        $userRoleChecker = new UserRoleChecker($this->em);
        $orgs = $userRoleChecker->organisations($this->owner, []);
        self::assertCount(4, $orgs);
    }
}
