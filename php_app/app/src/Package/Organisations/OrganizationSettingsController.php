<?php


namespace App\Package\Organisations;


use App\Models\OrganizationSettings;
use App\Utils\Http;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

class OrganizationSettingsController
{
    /**
     * @var OrganizationSettingsService $organizationSettingsService
     */
    private $organizationSettingsService;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * OrganizationSettingsController constructor.
     * @param OrganizationSettingsService $organizationSettingsService
     * @param OrganizationProvider $organizationProvider
     */
    public function __construct(
        OrganizationSettingsService $organizationSettingsService,
        OrganizationProvider $organizationProvider
    ) {
        $this->organizationSettingsService = $organizationSettingsService;
        $this->organizationProvider        = $organizationProvider;
    }

    public function getSettings(Request $request, Response $response): Response
    {
        $organization = $this->organizationProvider->organizationForRequest($request);
        return $response->withJson(
            $this->organizationSettingsService->settings($organization->getId())
        );
    }

    public function updateSettings(Request $request, Response $response): Response
    {
        $organization = $this->organizationProvider->organizationForRequest($request);
        $input        = OrganizationSettingsInput::fromArray($request->getParsedBody());
        try {
            $settings = $this->organizationSettingsService->updateSettings(
                $organization->getId(),
                $input->getVersion(),
                $input->getSettings()
            );
        } catch (Throwable $throwable) {
            return $response->withJson(
                Http::status(400, "something went wrong, version?"),
                400
            );
        }
        return $response->withJson(
            $settings
        );
    }
}