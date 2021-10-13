<?php


namespace App\Package\Member;


use App\Models\Organization;
use App\Package\Organisations\OrganizationService;
use App\Package\RequestUser\UserProvider;
use InvalidArgumentException;
use Slim\Http\Request;
use Slim\Http\Response;

class PartnerCustomerController
{
    /**
     * @var OrganizationService $organisationService
     */
    private $organisationService;

    /**
     * @var ResellerOrganisationService $resellerOrganisationService
     */
    private $resellerOrganisationService;

    /**
     * PartnerCustomerController constructor.
     * @param OrganizationService $organisationService
     * @param ResellerOrganisationService $resellerOrganisationService
     */
    public function __construct(
        OrganizationService $organisationService,
        ResellerOrganisationService $resellerOrganisationService
    )
    {
        $this->organisationService         = $organisationService;
        $this->resellerOrganisationService = $resellerOrganisationService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createCustomer(Request $request, Response $response)
    {
        $resellerOrgId = $request->getAttribute('resellerOrgId');
        $reseller = $this->organisationService->getOrganisationById($resellerOrgId);
        if ($reseller->getType() !== Organization::ResellerType && $reseller->getType() !== Organization::RootType) {
            return $response->withStatus(400, "Organisation is not a reseller");
        }
        try {
            $userInput = UserCreationInput::createFromArray($request->getParsedBody());
            if ($userInput->getReseller() === null) {
                $userInput->setReseller($reseller->getOwnerId()->toString());
            }
            $org = $this->resellerOrganisationService->createUserAndOrganisation($reseller, $userInput);
        }catch (InvalidArgumentException $e) {
            return $response->withStatus(400, $e->getMessage());
        }
        return $response->withJson($org, 201);
    }
}