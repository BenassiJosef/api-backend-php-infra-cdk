<?php
/**
 * Created by jamieaitken on 10/10/2018 at 09:26
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\CampaignMonitor;

use App\Models\Integrations\CampaignMonitor\CampaignMonitorContactList;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorListLocation;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorUserDetails;
use App\Models\Integrations\FilterEventList;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\CacheEngine;
use Curl\Curl;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class CampaignMonitorContactListController
{
    protected $em;
    private $cache;
    private $resourceUrl = 'https://api.createsend.com/api/v3.2/';


    public function __construct(EntityManager $em)
    {
        $this->em    = $em;
        $this->cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));

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

    public function getAll(string $serial)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.contactListName, u.id')
            ->from(CampaignMonitorListLocation::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->andWhere('u.deleted = :false')
            ->setParameter('false', false)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'NO_CAMPAIGN_MONITOR_DETAILS_FOR_THIS_LOCATION');
        }

        return Http::status(200, $get);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $send = $this->delete($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function delete($id)
    {
        $location = $this->em->getRepository(CampaignMonitorListLocation::class)->findOneBy([
            'id' => $id
        ]);

        $location->deleted = true;
        $location->enabled = false;

        $this->em->persist($location);
        $this->em->flush();

        return Http::status(200);
    }

    public function getSpecific(string $id)
    {
        $get = $this->em->createQueryBuilder()
            ->select('u.contactListId, u.contactListName, u.onEvent, u.enabled, i.id as filterId, i.name, u.id')
            ->from(CampaignMonitorListLocation::class, 'u')
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
        $getAPIKey = $this->em->createQueryBuilder()
            ->select('u.apiKey')
            ->from(CampaignMonitorUserDetails::class, 'u')
            ->leftJoin(CampaignMonitorContactList::class, 'p', 'WITH', 'u.id = p.details')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($getAPIKey)) {
            return Http::status(204);
        }

        $getLists   = new Curl();
        $getClients = new Curl();
        $getClients->setBasicAuthentication($getAPIKey[0]['apiKey']);
        $getLists->setBasicAuthentication($getAPIKey[0]['apiKey']);

        $getClientsResponse = $getClients->get($this->resourceUrl . 'clients.json');

        if ($getClients->httpStatusCode !== 200) {
            return Http::status(400, 'EXTERNAL_API_ERROR');
        }

        $lists = [];

        foreach ($getClientsResponse as $client) {
            $response = $getLists->get($this->resourceUrl . 'clients/' . $client->ClientID . '/lists.json');

            if ($getLists->httpStatusCode !== 200) {
                continue;
            }

            foreach ($response as $list) {
                $previouslySynced = $this->em->getRepository(CampaignMonitorListLocation::class)->findOneBy([
                    'contactListId' => $list->ListID
                ]);

                if (is_object($previouslySynced)) {
                    $previouslySynced->contactListName = $list->Name;
                }
                $lists[] = [
                    'id'   => $list->ListID,
                    'name' => $list->Name
                ];
            }

        }

        $this->em->flush();


        return Http::status(200, $lists);
    }

    public function update(string $serial, string $orgId, array $body)
    {
        $userDetails = $this->em->getRepository(CampaignMonitorContactList::class)->findOneBy([
            'organizationId' => $orgId
        ]);

        if (is_null($userDetails)) {
            return Http::status(400, 'CAN_NOT_LOCATE_USER_CREDENTIALS');
        }

        if (isset($body['id'])) {

            $location = $this->em->getRepository(CampaignMonitorListLocation::class)->findOneBy([
                'id' => $body['id']
            ]);

            if (is_null($location)) {
                return Http::status(400, 'ID_INVALID');
            }

        } else {
            $location = new CampaignMonitorListLocation($serial, $userDetails->details,
                $body['contactListId'],
                $body['contactListName'],
                $body['onEvent']
            );

            $this->em->persist($location);
        }

        $location->contactListId   = $body['contactListId'];
        $location->contactListName = $body['contactListName'];
        $location->onEvent         = $body['onEvent'];
        if (isset($body['enabled'])) {
            $location->enabled = $body['enabled'];
        }

        $location->filterListId = $body['filterListId'];


        $this->em->flush();

        $response['id']              = $location->id;
        $response['contactListId']   = $location->contactListId;
        $response['contactListName'] = $location->contactListName;
        $response['filterListId']    = $location->filterListId;
        $response['enabled']         = $location->enabled;
        $response['onEvent']         = $location->onEvent;

        $this->cache->delete('campaignMonitor:' . $serial);

        return Http::status(200, $response);
    }
}