<?php
/**
 * Created by jamieaitken on 01/10/2018 at 14:26
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\dotMailer;

use App\Models\Integrations\DotMailer\DotMailerContactList;
use App\Models\Integrations\DotMailer\DotMailerUserDetails;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use Curl\Curl;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class DotMailerSetupController
{
    protected $em;
    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
            ->from(DotMailerUserDetails::class, 'u')
            ->leftJoin(DotMailerContactList::class, 'p', 'WITH', 'u.id = p.details')
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

        $getGroups = new Curl();

        $authDetails = explode(';', $body['apiKey']);

        $getGroups->setBasicAuthentication($authDetails[0], $authDetails[1]);
        $response = $getGroups->get('https://api.dotmailer.com/v2/address-books');

        if ($response->httpStatusCode !== 200) {
            return Http::status(400, 'API_KEY_INVALID');
        }

        $user = $this->em->getRepository(DotMailerContactList::class)->findOneBy([
            'organizationId' => $organization->getId()
        ]);

        if (is_null($user)) {
            $user = new DotMailerUserDetails($body['apiKey']);
            $this->em->persist($user);

            $assignToUser = new DotMailerContactList($organization, $user->id);
            $this->em->persist($assignToUser);

        } else {

            $hasPermissionToEdit = $this->em->getRepository(DotMailerContactList::class)->findOneBy([
                'organizationId' => $organization->getId(),
                'details' => $user->details
            ]);

            if (is_null($hasPermissionToEdit)) {
                return Http::status(409, 'USER_CAN_NOT_EDIT_KEY');
            }

            $updateKey = $this->em->getRepository(DotMailerUserDetails::class)->findOneBy([
                'id' => $user->details
            ]);

            $updateKey->apiKey = $body['apiKey'];
        }

        $this->em->flush();

        return Http::status(200, ['apiKey' => $body['apiKey']]);
    }
}