<?php
/**
 * Created by jamieaitken on 26/09/2018 at 14:42
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Textlocal;

use App\Models\Integrations\TextLocal\TextLocalContactList;
use App\Models\Integrations\TextLocal\TextLocalUserDetails;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use Curl\Curl;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;


class TextLocalSetupController
{

    protected $em;
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    public function __construct(EntityManager $em)
    {
        $this->em            = $em;
        $this->orgIdProvider = new OrganisationIdProvider($this->em);
    }

    public function updateUserDetailsRoute(Request $request, Response $response)
    {
        $send = $this->updateUserDetails($request->getAttribute('orgId'), $request->getParsedBody());

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

        $getGroups = new Curl();
        $response  = $getGroups->post('https://api.txtlocal.com/get_groups/', [
            'apikey' => $body['apiKey']
        ]);

        if ($response->status !== 'success') {
            return Http::status(400, 'API_KEY_INVALID');
        }

        $user = $this->em->getRepository(TextLocalContactList::class)->findOneBy([
            'organizationId' => $organization->getId()
        ]);

        if (is_null($user)) {
            $user = new TextLocalUserDetails($body['apiKey']);
            $this->em->persist($user);

            $assignToUser = new TextLocalContactList($organization, $user->id);
            $this->em->persist($assignToUser);

        } else {

            $hasPermissionToEdit = $this->em->getRepository(TextLocalContactList::class)->findOneBy([
                'organizationId' => $organization->getId(),
                'details'        => $user->details
            ]);

            if (is_null($hasPermissionToEdit)) {
                return Http::status(409, 'USER_CAN_NOT_EDIT_KEY');
            }

            $updateKey = $this->em->getRepository(TextLocalUserDetails::class)->findOneBy([
                'id' => $user->details
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
            ->from(TextLocalUserDetails::class, 'u')
            ->leftJoin(TextLocalContactList::class, 'p', 'WITH', 'u.id = p.details')
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