<?php
/**
 * Created by jamieaitken on 09/10/2018 at 09:59
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\ConstantContact;

use App\Controllers\Branding\_BrandingController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Models\Integrations\ConstantContact\ConstantContactList;
use App\Models\Integrations\ConstantContact\ConstantContactUserDetails;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use Curl\Curl;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class ConstantContactAuthorize
{
    protected $em;
    private $authorizeUrl = 'https://oauth2.constantcontact.com/oauth2/oauth/siteowner/authorize';
    private $redirectUrl = 'https://api.blackbx.io/oauth/constant-contact?uid=';
    private $accessTokenUrl = 'https://oauth2.constantcontact.com/oauth2/oauth/token';

    static $apiKey = 'xc7aejbgn3d542yc65ms4d8n';
    static $clientSecret = 'bb3Da8bsMXrCbwPMvAsnv6kc';

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function getAuthorisationCodeRoute(Request $request, Response $response)
    {
        $send = $this->getAuthorisationCode($request->getAttribute('orgId'));

        return $response->withJson($send, $send['status']);
    }

    public function getAccessTokenRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->getAccessToken($request->getQueryParams(), $organization);

        if ($send['status'] !== 302) {
            return $response->withJson($send['status']);
        }

        return $response->withStatus($send['status'])->withHeader('Location',
            $send['message']['domain'] . 'integration/constant-contact');
    }

    public function getAuthorisationCode(string $orgId)
    {

        $request = $this->authorizeUrl . '?' . http_build_query([
                'response_type' => 'code',
                'client_id'     => 'xc7aejbgn3d542yc65ms4d8n',
                'redirect_uri'  => $this->redirectUrl . $orgId
            ]);

        return Http::status(302, $request);
    }

    public function getAccessToken(array $params, Organization $organization)
    {

        $request = new Curl();

        $query = $this->accessTokenUrl . '?' . http_build_query([
                'grant_type'    => 'authorization_code',
                'client_id'     => self::$apiKey,
                'client_secret' => self::$clientSecret,
                'code'          => $params['code'],
                'redirect_uri'  => $this->redirectUrl . $organization->getId()
            ]);

        $response = $request->post($query);

        if (isset($response->error)) {
            return Http::status(400, 'Error');
        }

        $newDetails = new ConstantContactUserDetails($response->access_token);
        $this->em->persist($newDetails);

        $newUserDetails = new ConstantContactList($organization, $newDetails->id);
        $this->em->persist($newUserDetails);

        $this->em->flush();
        $brandingController = new _BrandingController($this->em);
        $brand              = $brandingController->default();

        return Http::status(302, $brand);
    }
}