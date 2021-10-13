<?php


namespace App\Package\Organisations;


use App\Controllers\User\UserOverviewController;
use App\Controllers\WebTracker\WebTrackingController;
use App\Models\MarketingCampaigns;
use App\Package\Filtering\UserFilter;
use App\Package\RequestUser\UserProvider;
use App\Package\WebForms\Settings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class OrganisationProgress
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    /**
     * @var OrganizationService
     */
    private $organizationService;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * OrganisationProgress constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->organisationProvider = new OrganizationProvider($this->em);
        $this->organizationService = new OrganizationService($this->em);
        $this->userProvider = new UserProvider($this->em);
    }

    public function get(Request $request, Response $response): Response
    {

        $orgId = $request->getAttribute('orgId');
        $organization = $this->organisationProvider->organizationForRequest($request);
        $locations = iterator_to_array($organization->getLocations());
        $serial = [];
        foreach ($locations as $location) {
            $serial[] = $location->getSerial();
        }

        $formSettings = new Settings($this->em);
        $forms = $formSettings->getForms($request, $response);

        $webTracking = new WebTrackingController($this->em);
        $websites = $webTracking->listWebsites($request, $response);

        $filter = new UserFilter($this->em);
        $totalContacts = $filter->getTotalCount($orgId, $serial, null);
        $orgUuId = Uuid::fromString($orgId);
        $users = $this->organizationService->getUsers($this->userProvider->getOauthUser($request), $orgUuId);

        $marketing = $this->getCampaigns($orgId);


        $res = Http::status(200, [
            'forms' => $forms->getStatusCode() === 200 ? true : false,
            'websites' => $websites->getStatusCode() === 200 ? true : false,
            'contacts' => $totalContacts > 0 ? true : false,
            'contacts_found' => $totalContacts,
            'marketing' => count($marketing) > 0 ? true : false,
            'users' => count($users) > 1 ? true : false
        ]);

        return $response->withJson($res, $res['status']);
    }

    /**
     * @param $orgId
     * @return MarketingCampaigns[]|null
     */
    public function getCampaigns($orgId)
    {
        return $this->em->getRepository(MarketingCampaigns::class)->findBy([
            'organizationId' => $orgId,
            'deleted' => false
        ]);
    }


}