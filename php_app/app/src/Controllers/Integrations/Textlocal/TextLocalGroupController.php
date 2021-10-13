<?php
/**
 * Created by jamieaitken on 26/09/2018 at 16:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Textlocal;

use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\Integrations\TextLocal\TextLocalContactList;
use App\Models\Integrations\TextLocal\TextLocalContactListLocation;
use App\Models\Integrations\TextLocal\TextLocalUserDetails;
use App\Package\Organisations\OrganisationIdProvider;
use App\Utils\CacheEngine;
use Curl\Curl;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class TextLocalGroupController
{
    protected $em;
    private $cache;
    private $resourceUrl = 'https://api.txtlocal.com/get_groups/';
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    public function __construct(EntityManager $em)
    {
        $this->cache         = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
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
        $location = $this->em->getRepository(TextLocalContactListLocation::class)->findOneBy([
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
            ->from(TextLocalContactListLocation::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->andWhere('u.deleted = :false')
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'NO_TEXT_LOCAL_FOR_THIS_LOCATION');
        }

        return Http::status(200, $get);
    }

    public function getSpecific(string $id)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.contactListId, u.contactListName, u.onEvent, u.enabled, i.id as filterId, i.name, u.id')
            ->from(TextLocalContactListLocation::class, 'u')
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
        $response['filterListId']    = $get[0]['filterId'];
        $response['onEvent']         = $get[0]['onEvent'];
        $response['enabled']         = $get[0]['enabled'];
        $response['id']              = $get[0]['id'];

        return Http::status(200, $response);
    }

    public function getExternalGroup(string $orgId)
    {

        $getApiKey = $this->em->createQueryBuilder()
            ->select('u.apiKey')
            ->from(TextLocalUserDetails::class, 'u')
            ->leftJoin(TextLocalContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($getApiKey)) {
            return Http::status(204);
        }

        $getGroups = new Curl();
        $response  = $getGroups->post($this->resourceUrl, [
            'apikey' => $getApiKey[0]['apiKey']
        ]);

        if ($response->status !== 'success') {
            return Http::status(400, 'COULD_NOT_FETCH_GROUPS');
        }

        foreach ($response->groups as $group) {
            $previouslySynced = $this->em->getRepository(TextLocalContactListLocation::class)->findOneBy([
                'contactListId' => $group->id
            ]);

            if (is_object($previouslySynced)) {
                $previouslySynced->contactListName = $group->name;
            }
        }

        $this->em->flush();


        return Http::status(200, $response->groups);
    }

    public function update(string $serial, string $orgId, array $body)
    {
        $userDetails = $this->em->getRepository(TextLocalContactList::class)->findOneBy([
            'organizationId' => $orgId
        ]);

        $response = [];

        if (is_null($userDetails)) {
            return Http::status(400, 'CAN_NOT_LOCATE_USER_CREDENTIALS');
        }

        if (isset($body['id'])) {
            $location = $this->em->getRepository(TextLocalContactListLocation::class)->findOneBy([
                'id' => $body['id']
            ]);

            if (is_null($location)) {
                return Http::status(400, 'ID_INVALID');
            }

            $location->contactListId   = $body['contactListId'];
            $location->contactListName = $body['contactListName'];
            if (isset($body['enabled'])) {
                $location->enabled = $body['enabled'];
            }

        } else {
            $location = new TextLocalContactListLocation($serial, $userDetails->details,
                $body['contactListId'],
                $body['contactListName'],
                $body['onEvent']);

            $this->em->persist($location);
        }

        $location->onEvent = $body['onEvent'];

        $location->filterListId = $body['filterListId'];


        $this->em->flush();

        $response['id']              = $location->id;
        $response['contactListId']   = $location->contactListId;
        $response['contactListName'] = $location->contactListName;
        $response['filterListId']    = $location->filterListId;
        $response['enabled']         = $location->enabled;
        $response['onEvent']         = $location->onEvent;

        $this->cache->delete('textLocal:' . $serial);

        return Http::status(200, $response);
    }
}