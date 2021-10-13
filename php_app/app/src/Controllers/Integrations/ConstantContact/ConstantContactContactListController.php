<?php
/**
 * Created by jamieaitken on 09/10/2018 at 10:33
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\ConstantContact;

use App\Models\Integrations\ConstantContact\ConstantContactList;
use App\Models\Integrations\ConstantContact\ConstantContactListLocation;
use App\Models\Integrations\ConstantContact\ConstantContactUserDetails;

use App\Models\Integrations\FilterEventList;
use App\Package\Organisations\OrganisationIdProvider;
use Curl\Curl;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class ConstantContactContactListController
{
    protected $em;
    private $resourceUrl = 'https://api.constantcontact.com/v2/lists';
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    public function __construct(EntityManager $em)
    {
        $this->em            = $em;
        $this->orgIdProvider = new OrganisationIdProvider($this->em);
    }

    public function getAllRoute(Request $request, Response $response)
    {

        $send = $this->getAll($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSpecificRoute(Request $request, Response $response)
    {

        $send = $this->getSpecific($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getExternalRoute(Request $request, Response $response)
    {
        $send = $this->getExternalGroup($request->getAttribute('orgId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update($request->getAttribute('serial'), $request->getAttribute('orgId'),
            $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $send = $this->delete($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function delete($id)
    {
        $location = $this->em->getRepository(ConstantContactListLocation::class)->findOneBy([
            'id' => $id
        ]);

        $location->deleted = true;
        $location->enabled = false;

        $this->em->persist($location);
        $this->em->flush();

        return Http::status(200);
    }

    public function getAll(string $serial)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.contactListName, u.id')
            ->from(ConstantContactListLocation::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->andWhere('u.deleted = :false')
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'NO_CONSTANT_CONTACT_FOR_THIS_LOCATION');
        }

        return Http::status(200, $get);
    }

    public function getSpecific(string $id)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.contactListId, u.contactListName, u.onEvent, u.enabled, i.id as filterId, i.name, u.id')
            ->from(ConstantContactListLocation::class, 'u')
            ->leftJoin(FilterEventList::class, 'i', 'WITH', 'i.id = u.filterListId')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        $response['contactListId']   = $get[0]['contactListId'];
        $response['contactListName'] = $get[0]['contactListName'];
        $response['filterName']      = $get[0]['name'];
        $response['filterId']        = $get[0]['filterId'];
        $response['onEvent']         = $get[0]['onEvent'];
        $response['enabled']         = $get[0]['enabled'];
        $response['id']              = $get[0]['id'];

        return Http::status(200, $response);
    }

    public function getExternalGroup(string $orgId)
    {

        $getAccessToken = $this->em->createQueryBuilder()
            ->select('u.accessToken')
            ->from(ConstantContactUserDetails::class, 'u')
            ->leftJoin(ConstantContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($getAccessToken)) {
            return Http::status(204);
        }

        $getLists = new Curl();

        $getLists->setHeader('Authorization', 'Bearer ' . $getAccessToken[0]['accessToken']);
        $response = $getLists->get($this->resourceUrl);

        if ($getLists->httpStatusCode !== 200) {
            return Http::status(400, 'COULD_NOT_FETCH_LISTS');
        }


        foreach ($response as $group) {
            $previouslySynced = $this->em->getRepository(ConstantContactListLocation::class)->findOneBy([
                'contactListId' => $group->id
            ]);

            if (is_object($previouslySynced)) {
                $previouslySynced->contactListName = $group->name;
            }
        }

        $this->em->flush();


        return Http::status(200, $response);
    }

    public function update(string $serial, string $orgId, array $body)
    {
        $userDetails = $this->em->getRepository(ConstantContactList::class)->findOneBy([
            'organizationId' => $orgId
        ]);

        if (is_null($userDetails)) {
            return Http::status(400, 'CAN_NOT_LOCATE_USER_CREDENTIALS');
        }

        if (isset($body['id'])) {

            $location = $this->em->getRepository(ConstantContactListLocation::class)->findOneBy([
                'id' => $body['id']
            ]);

            if (is_null($location)) {
                return Http::status(400, 'ID_INVALID');
            }

        } else {
            $location = new ConstantContactListLocation($serial, $userDetails->details,
                $body['contactListId'],
                $body['contactListName'],
                $body['onEvent']);

            $this->em->persist($location);
        }

        $location->contactListId   = $body['contactListId'];
        $location->contactListName = $body['contactListName'];
        $location->onEvent         = $body['onEvent'];
        if (isset($body['enabled'])) {
            $location->enabled = $body['enabled'];
        }

        if (isset($body['filterListId'])) {
            $location->filterListId = $body['filterListId'];
        }

        $this->em->flush();

        $response['id']              = $location->id;
        $response['contactListId']   = $location->contactListId;
        $response['contactListName'] = $location->contactListName;
        $response['filterListId']    = $location->filterListId;
        $response['enabled']         = $location->enabled;
        $response['onEvent']         = $location->onEvent;


        return Http::status(200, $response);
    }
}