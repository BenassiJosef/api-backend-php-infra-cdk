<?php
/**
 * Created by jamieaitken on 10/10/2018 at 09:12
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\CampaignMonitor;

use App\Models\Integrations\CampaignMonitor\CampaignMonitorContactList;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorUserDetails;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use Curl\Curl;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class CampaignMonitorSetupController
{
    protected $em;
    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function getUserDetailsRoute(Request $request, Response $response)
    {

        $send = $this->getUserDetails($request->getAttribute('orgId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateUserDetailsRoute(Request $request, Response $response)
    {

        $organization = $this->organisationProvider->organizationForRequest($request);
        $send = $this->updateUserDetails($organization, $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getUserDetails(string $orgId)
    {

        $get = $this->em->createQueryBuilder()
            ->select('u.apiKey')
            ->from(CampaignMonitorUserDetails::class, 'u')
            ->leftJoin(CampaignMonitorContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        return Http::status(200, ['apiKey' => $get[0]['apiKey']]);
    }

    public function updateUserDetails(Organization $organization, array $body)
    {
        if (!isset($body['apiKey'])) {
            return Http::status(400, 'API_KEY_MISSING');
        }

        $getClients = new Curl();
        $getClients->setBasicAuthentication($body['apiKey']);
        $getClientsResponse = $getClients->get('https://api.createsend.com/api/v3.2/clients.json');
        if ($getClientsResponse->httpStatusCode !== 200) {
            return Http::status(400, 'API_KEY_INVALID');
        }

        $list         = $this->em->getRepository(CampaignMonitorContactList::class)->findOneBy([
            'organizationId' => $organization->getId()
        ]);

        if (is_null($list)) {
            $details = new CampaignMonitorUserDetails($body['apiKey']);
            $this->em->persist($details);

            $assignToUser = new CampaignMonitorContactList($organization, $details->id);
            $this->em->persist($assignToUser);

        } else {

            $hasPermissionToEdit = $this->em->getRepository(CampaignMonitorContactList::class)->findOneBy([
                'organizationId' => $organization->getId(),
                'details'        => $list->details
            ]);

            if (is_null($hasPermissionToEdit)) {
                return Http::status(409, 'USER_CAN_NOT_EDIT_KEY');
            }

            $updateKey = $this->em->getRepository(CampaignMonitorUserDetails::class)->findOneBy([
                'id' => $list->details
            ]);

            $updateKey->apiKey = $body['apiKey'];
        }

        $this->em->flush();

        return Http::status(200, ['apiKey' => $body['apiKey']]);
    }
}