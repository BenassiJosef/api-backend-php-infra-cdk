<?php

namespace StampedeTests\app\src\Package\Member;

use App\Controllers\Auth\_PasswordController;
use App\Models\OauthUser;
use App\Models\Role;
use App\Package\Member\EmailValidator;
use App\Package\Member\MemberService;
use App\Package\Member\UserCreationInput;
use App\Package\Organisations\OrganizationService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class MemberServiceTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
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

    public function testCreateUserSendsResetEmail()
    {
        $passwordController = $this
            ->createMock(_PasswordController::class);
        $passwordController
            ->expects($this->once())
            ->method('forgotPassword')
            ->with($this->anything());

        $emailValidator = $this->createMock(EmailValidator::class);
        $emailValidator
            ->expects($this->once())
            ->method('validateEmail')
            ->with($this->anything())
            ->willReturn(true);

        $data           = [
            'admin'                => null,
            'reseller'             => '675D83A2-7985-49FF-9EB1-36ABD62B900F',
            'email'                => 'bob@example.com',
            'password'             => null,
            'company'              => null,
            'organisationId'       => null,
            'parentOrganisationId' => null,
            'first'                => 'bob',
            'last'                 => 'bobertson',
            'role'                 => Role::LegacyAdmin,
            'country'              => 'GB',
        ];
        $input          = UserCreationInput::createFromArray($data);

        $organisationService = new OrganizationService($this->em);

        $membersService = new MemberService($this->em, $emailValidator, $passwordController, $organisationService);
        $membersService->createUser($input);
        $this->em->flush();
        $this->em->clear();
        $fetchedUser = $this->em->getRepository(OauthUser::class)->findOneBy(
            [
                'email' => 'bob@example.com'
            ]
        );
        self::assertNotNull($fetchedUser);
    }
}
