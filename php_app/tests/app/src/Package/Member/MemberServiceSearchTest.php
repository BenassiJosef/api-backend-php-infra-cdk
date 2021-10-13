<?php
/**
 * Created by chrisgreening on 11/03/2020 at 09:17
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace StampedeTests\app\src\Package\Member;

use App\Controllers\Auth\_PasswordController;
use App\Package\Member\EmailValidator;
use App\Package\Member\MemberService;
use App\Package\Organisations\OrganizationService;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class MemberServiceSearchTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    private $user1;
    private $user2;
    private $user3;

    public function setUp(): void
    {
        $this->em    = DoctrineHelpers::createEntityManager();
        $this->user1 = EntityHelpers::createOauthUser($this->em, "bob@banana.com", "password123", "Dave's dodgy deals", "", "Peter", "Rabbit");
        $this->user2 = EntityHelpers::createOauthUser($this->em, "curt@kobain.com", "password123", "Nirvana", "", "curt", "kobain");
        $this->user3 = EntityHelpers::createOauthUser($this->em, "ilike@banana.com", "password123", "Meal Deal", "", "Peter", "Jones");
        $this->em->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->em->remove($this->user1);
        $this->em->remove($this->user2);
        $this->em->remove($this->user3);
        $this->em->flush();
        $this->em->rollback();
    }

    public function testGetUsers()
    {
        $passwordController = $this
            ->createMock(_PasswordController::class);

        $emailValidator      = $this->createMock(EmailValidator::class);
        $organisationService = new OrganizationService($this->em);

        $membersService = new MemberService($this->em, $emailValidator, $passwordController, $organisationService);


        self::assertCount(13, $membersService->search(0, 100));
        self::assertCount(2, $membersService->search(0, 100, "peter"));
        self::assertCount(2, $membersService->search(0, 100, "banana"));
    }
}
