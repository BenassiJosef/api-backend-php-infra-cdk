<?php


namespace App\Package\WebForms;


use App\Models\DataSources\DataSource;
use App\Models\DataSources\InteractionProfile;
use App\Models\UserProfile;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\Organisations\OrganizationProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class EmailSignupController
{
    /**
     * @var ProfileInteractionFactory $profileInteractionFactory
     */
    private $profileInteractionFactory;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * EmailSignupController constructor.
     * @param ProfileInteractionFactory $profileInteractionFactory
     * @param OrganizationProvider $organizationProvider
     */
    public function __construct(
        ProfileInteractionFactory $profileInteractionFactory,
        OrganizationProvider $organizationProvider
    ) {
        $this->profileInteractionFactory = $profileInteractionFactory;
        $this->organizationProvider      = $organizationProvider;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function registerEmail(Request $request, Response $response): Response
    {
        $requestLocations = $this->organizationProvider->requestLocations($request);
        $dataSource         = $this->dataSourceForRequest($request);
        $profileInteraction = $this->profileInteractionFactory->makeEmailingProfileInteraction(
            new InteractionRequest(
                $requestLocations->commonParent(),
                $dataSource,
                $requestLocations->serials()
            )
        );

        $candidateProfile = CandidateProfile::fromArray($request->getParsedBody());
        $profileInteraction->saveCandidateProfile($candidateProfile);
        $profileInfo = $profileInteraction->getLastInsertedProfileInformation();
        return $response->withJson(
            $profileInfo
        );
    }

    /**
     * @param Request $request
     * @return DataSource
     * @throws Exception
     */
    private function dataSourceForRequest(Request $request): DataSource
    {
        $source = $request->getParam('source', 'web');
        if ($source === 'zapier') {
            $source = 'api';
        }
        return $this
            ->profileInteractionFactory
            ->getDataSource($source);
    }
}