<?php

/**
 * Created by jamieaitken on 28/09/2018 at 14:25
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\MailChimp;

use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Models\Integrations\MailChimp\MailChimpContactList;
use App\Models\Integrations\MailChimp\MailChimpUserDetails;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use Curl\Curl;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class MailChimpSetupController
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

    public function getResourceUrl(string $apiKey)
    {
        $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        return 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists';
    }

    public function updateUserDetailsRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->updateUserDetails($organization, $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getUserDetailsRoute(Request $request, Response $response)
    {

        $send = $this->getUserDetails($request->getAttribute('orgId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateUserDetails(Organization $organization, array $body)
    {
        if (!isset($body['apiKey'])) {
            return Http::status(400, 'API_KEY_MISSING');
        }

        $request = new Curl();

        $request->setBasicAuthentication('user', $body['apiKey']);
        $resourceUrl = $this->getResourceUrl($body['apiKey']);
        $request->get($resourceUrl);

        if ($request->error) {
            newrelic_add_custom_parameter('mailchimp_url', $resourceUrl);
            newrelic_notice_error(new Exception('cannot set api key'));
            return Http::status(400, [
                'message' => 'API_KEY_INVALID',
                'resourse_url' => $resourceUrl,
                'status' => $request->httpStatusCode
            ]);
        }


        $list = $this->em->getRepository(MailChimpContactList::class)->findOneBy([
            'organizationId' => $organization->getId()
        ]);


        if (is_null($list)) {
            $details = new MailChimpUserDetails($body['apiKey']);
            $this->em->persist($details);


            $assignToUser = new MailChimpContactList($organization, $details->id);
            $this->em->persist($assignToUser);
        } else {

            $hasPermissionToEdit = $this->em->getRepository(MailChimpContactList::class)->findOneBy([
                'organizationId' => $organization->getId(),
                'details'        => $list->details
            ]);

            if (is_null($hasPermissionToEdit)) {
                return Http::status(409, 'USER_CAN_NOT_EDIT_KEY');
            }

            $updateKey = $this->em->getRepository(MailChimpUserDetails::class)->findOneBy([
                'id' => $list->details
            ]);

            $updateKey->apiKey = $body['apiKey'];
        }

        $this->em->flush();

        return Http::status(200, ['apiKey' => $body['apiKey']]);
    }

    public function getUserDetails(string $orgId)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.apiKey')
            ->from(MailChimpUserDetails::class, 'u')
            ->leftJoin(MailChimpContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        return Http::status(200, ['apiKey' => $get[0]['apiKey']]);
    }
}
