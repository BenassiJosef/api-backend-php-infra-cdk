<?php
/**
 * Created by jamieaitken on 2019-07-04 at 12:58
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Airship;


use App\Models\Integrations\Airship\AirshipContactList;
use App\Models\Integrations\Airship\AirshipUserDetails;
use App\Models\Integrations\DotMailer\DotMailerContactList;
use App\Models\Integrations\DotMailer\DotMailerUserDetails;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class AirshipSetupController
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
        $send         = $this->updateUserDetails($organization, $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getUserDetails(string $orgId)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.apiKey, u.id')
            ->from(AirshipUserDetails::class, 'u')
            ->leftJoin(AirshipContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        return Http::status(200, $get);
    }

    public function updateUserDetails(Organization $organization, array $body)
    {
        if (!isset($body['apiKey'])) {
            return Http::status(400, 'API_KEY_MISSING');
        }

        //username, password, source, name

        $authDetails = explode(';', $body['apiKey']);

        $client = new \SoapClient("https://secure.airship.co.uk/SOAP/V3/Admin.wsdl");

        $myDetails = $client->getSystemUsers($authDetails[0], $authDetails[1]);

        if ($myDetails instanceof \SoapFault) {
            return Http::status(400, 'API_KEY_INVALID');
        }


        if (isset($body['id'])) {

            $userDetails = $this->em->getRepository(AirshipUserDetails::class)->findOneBy([
                'id' => $body['id']
            ]);

            if (is_null($userDetails)) {
                return Http::status(204);
            }


            $hasPermissionToEdit = $this->em->getRepository(AirshipContactList::class)->findOneBy([
                'organizationId' => $organization->getId(),
                'details'        => $userDetails->id
            ]);


            if (is_null($hasPermissionToEdit)) {
                return Http::status(409, 'USER_CAN_NOT_EDIT_KEY');
            }

            $userDetails->apiKey = $body['apiKey'];

        } else {
            $details = new AirshipUserDetails($body['apiKey']);
            $this->em->persist($details);

            $assignToUser = new AirshipContactList($organization, $details->id);
            $this->em->persist($assignToUser);
        }


        $this->em->flush();

        $getUpdatedResults = $this->em->createQueryBuilder()
            ->select('u.id, u.apiKey')
            ->from(AirshipUserDetails::class, 'u')
            ->where('u.apiKey = :key')
            ->setParameter('key', $body['apiKey'])
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $getUpdatedResults[0]);
    }
}