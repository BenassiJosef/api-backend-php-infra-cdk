<?php

namespace StampedeTests\app\src\Package\Member;

use App\Controllers\Auth\_PasswordController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Member\EmailValidator;
use App\Package\Member\MemberService;
use App\Package\Member\ResellerOrganisationService;
use App\Package\Member\UserCreationInput;
use App\Package\Organisations\OrganizationService;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class ResellerOrganisationServiceTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    /**
     * @var \App\Models\OauthUser
     */
    private $owner;
    /**
     * @var \App\Models\Organization
     */
    private $parent;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->owner  = EntityHelpers::createOauthUser($this->em, "re@seller.com", "", "Reseller", "", "Re", "Seller");
        $this->parent = EntityHelpers::createOrganisation($this->em, "Reseller McResellers", $this->owner);
        $this->parent->setType(Organization::ResellerType);
        $this->em->persist($this->parent);
        $this->em->flush();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testCreateUserAndOrganisation()
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

        $data                        = [
            'admin'                         => null,
            'reseller'                      => '675D83A2-7985-49FF-9EB1-36ABD62B900F',
            'email'                         => 'bob@example.com',
            'password'                      => null,
            'company'                       => null,
            'organisationId'                => null,
            'parentOrganisationId'          => null,
            'first'                         => 'bob',
            'last'                          => 'bobertson',
            'role'                          => Role::LegacyAdmin,
            'country'                       => 'GB',
            'shouldCreateChargebeeCustomer' => true
        ];
        $input                       = UserCreationInput::createFromArray($data);
        $organisationService         = new OrganizationService($this->em);
        $membersService              = new MemberService($this->em, $emailValidator, $passwordController, $organisationService);
        $organisationService         = new OrganizationService($this->em);
        $chargeBeeCustomerController = $this->createMock(_ChargeBeeCustomerController::class);
        $chargeBeeCustomerController->expects(self::once())->method('createCustomer')->with(self::anything());
        $resellerOrganisationService = new ResellerOrganisationService($membersService, $organisationService, $chargeBeeCustomerController, $this->em);
        $org                         = $resellerOrganisationService->createUserAndOrganisation($this->parent, $input);
        self::assertNotNull($org);
        self::assertNotNull($org->getOwner());
        self::assertNotNull($org->getChargebeeCustomerId());
    }

    public function testCreateUserAndOrganisationNoChargebee()
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

        $data                        = [
            'admin'                         => null,
            'reseller'                      => '675D83A2-7985-49FF-9EB1-36ABD62B900F',
            'email'                         => 'bob@example.com',
            'password'                      => null,
            'company'                       => null,
            'organisationId'                => null,
            'parentOrganisationId'          => null,
            'first'                         => 'bob',
            'last'                          => 'bobertson',
            'role'                          => Role::LegacyAdmin,
            'country'                       => 'GB',
            'shouldCreateChargebeeCustomer' => false
        ];
        $input                       = UserCreationInput::createFromArray($data);
        $organisationService         = new OrganizationService($this->em);
        $membersService              = new MemberService($this->em, $emailValidator, $passwordController, $organisationService);
        $organisationService         = new OrganizationService($this->em);
        $chargeBeeCustomerController = $this->createMock(_ChargeBeeCustomerController::class);
        $resellerOrganisationService = new ResellerOrganisationService($membersService, $organisationService, $chargeBeeCustomerController, $this->em);
        $org                         = $resellerOrganisationService->createUserAndOrganisation($this->parent, $input);
        self::assertNotNull($org);
        self::assertNotNull($org->getOwner());
        self::assertNull($org->getChargebeeCustomerId());
    }
}
