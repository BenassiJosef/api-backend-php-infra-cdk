<?php


namespace App\Package\Member;


use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Models\Organization;
use App\Package\Billing\ChargebeeCustomer;
use App\Package\Organisations\OrganizationService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class ResellerOrganisationService
{
    /**
     * @var MemberService $memberService
     */
    private $memberService;

    /**
     * @var OrganizationService $organisationService
     */
    private $organisationService;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;
    /**
     * @var _ChargeBeeCustomerController
     */
    private $chargeBeeCustomerController;

    /**
     * ResellerOrganisationService constructor.
     * @param MemberService $memberService
     * @param OrganizationService $organisationService
     * @param _ChargeBeeCustomerController $chargeBeeCustomerController
     * @param EntityManager $entityManager
     */
    public function __construct(
        MemberService $memberService,
        OrganizationService $organisationService,
        _ChargeBeeCustomerController $chargeBeeCustomerController,
        EntityManager $entityManager
    )
    {
        $this->memberService       = $memberService;
        $this->organisationService = $organisationService;
        $this->entityManager       = $entityManager;
        $this->chargeBeeCustomerController = $chargeBeeCustomerController;
    }

    /**
     * @param Organization $parent
     * @param UserCreationInput $input
     * @return Organization
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createUserAndOrganisation(Organization $parent, UserCreationInput $input): Organization
    {
        $user = $this->memberService->createUser($input);
        $chargeBeeCustomerId = null;
        if($input->shouldCreateChargebeeCustomer()) {
            $chargeBeeCustomer = new ChargebeeCustomer($user);
            $this->chargeBeeCustomerController->createCustomer($chargeBeeCustomer);
            $chargeBeeCustomerId = $chargeBeeCustomer->getId();
        }
        $organisation = $this->organisationService->createOrganizationForOwner(
            $user,
            $input->getCompany(),
            $parent,
            $chargeBeeCustomerId);
        $this->entityManager->flush();
        return $organisation;
    }
}