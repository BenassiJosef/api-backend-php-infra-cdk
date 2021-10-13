<?php
/**
 * Created by jamieaitken on 2019-07-04 at 15:46
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Airship;


use App\Models\Integrations\Airship\AirshipContactList;
use App\Models\Integrations\Airship\AirshipListLocation;
use App\Models\Integrations\Airship\AirshipUserDetails;
use App\Models\Integrations\DotMailer\DotMailerAddressLocation;
use App\Models\Integrations\DotMailer\DotMailerContactList;
use App\Models\Integrations\DotMailer\DotMailerUserDetails;
use App\Models\Integrations\FilterEventList;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;

class AirshipGroupController
{
    protected $em;
    private   $cache;

    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    /**
     * @var OrganizationProvider $organisationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->cache                = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->orgIdProvider        = new OrganisationIdProvider($this->em);
        $this->organisationProvider = new OrganizationProvider($this->em);
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

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws MappingException
     * @throws Exception
     */
    public function getExternalRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->getExternalGroup($organization, $request->getQueryParam('detailsId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $send = $this->update(
            $request->getAttribute('serial'),
            $organization,
            $request->getParsedBody()
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getAll(string $serial)
    {
        $get = $this->em->createQueryBuilder()
                        ->select('u.contactListName, u.id, u.detailsId')
                        ->from(AirshipListLocation::class, 'u')
                        ->where('u.serial = :serial')
                        ->setParameter('serial', $serial)
                        ->andWhere('u.deleted = :false')
                        ->setParameter('false', false)
                        ->getQuery()
                        ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'NO_AIRSHIP_FOR_THIS_LOCATION');
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
        $location = $this->em->getRepository(AirshipListLocation::class)->findOneBy(
            [
                'id' => $id
            ]
        );

        $location->deleted = true;
        $location->enabled = false;

        $this->em->persist($location);
        $this->em->flush();

        return Http::status(200, $id);
    }

    public function getSpecific(string $id)
    {
        $get = $this->em->createQueryBuilder()
                        ->select('u.contactListId, u.contactListName, u.detailsId, u.onEvent, u.enabled, i.id as filterId, i.name, u.id')
                        ->from(AirshipListLocation::class, 'u')
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
        $response['detailsId']       = $get[0]['detailsId'];

        return Http::status(200, $response);
    }

    public function getExternalGroup(Organization $organization, string $detailId)
    {
        //TODO: Modify AirshipUserDetails to use organization, as the parameter iss not used.
        $getApiKey = $this->em->createQueryBuilder()
                              ->select('u.apiKey')
                              ->from(AirshipUserDetails::class, 'u')
                              ->where('u.id = :id')
                              ->setParameter('id', $detailId)
                              ->getQuery()
                              ->getArrayResult();

        if (empty($getApiKey)) {
            return Http::status(204);
        }

        $apiKey = $getApiKey[0]['apiKey'];
        //username, password, source, name
        $wsdl   = "https://secure.airship.co.uk/SOAP/V3/Stat.wsdl";
        $client = new \SoapClient($wsdl);

        $authDetails = explode(';', $apiKey);

        $unitRequest = $client->unitList($authDetails[0], $authDetails[1]);

        if ($unitRequest instanceof \SoapFault) {
            return Http::status(400, 'COULD_NOT_FETCH_UNITS');
        }


        $apiResponse = [];

        foreach ($unitRequest as $unit) {
            $response = $client->groupList($authDetails[0], $authDetails[1], $unit->unitid);
            foreach ($response as $groupUnit) {
                foreach ($groupUnit->groups as $group) {
                    $previouslySynced = $this->em->getRepository(AirshipListLocation::class)->findOneBy(
                        [
                            'contactListId' => $group->groupid
                        ]
                    );

                    if (is_object($previouslySynced)) {
                        $previouslySynced->contactListName = $groupUnit->name . '-' . $group->groupname;
                    }


                    $apiResponse[] = [
                        'name' => $unit->name . '-' . $group->groupname,
                        'id'   => (string)$group->groupid
                    ];
                }
            }

        }
        $this->em->flush();


        return Http::status(200, $apiResponse);
    }

    public function update(string $serial, Organization $organization, array $body)
    {
        $userDetails  = $this->em->getRepository(AirshipContactList::class)->findOneBy(
            [
                'organizationId' => $organization->getId()->toString()
            ]
        );

        if (is_null($userDetails)) {
            return Http::status(400, 'CAN_NOT_LOCATE_USER_CREDENTIALS');
        }

        if (isset($body['id'])) {

            $location = $this->em->getRepository(AirshipListLocation::class)->findOneBy(
                [
                    'id' => $body['id']
                ]
            );

            if (is_null($location)) {
                return Http::status(400, 'ID_INVALID');
            }

        } else {
            $location = new AirshipListLocation(
                $serial, $userDetails->details,
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
        $response['detailsId']       = $location->detailsId;

        $this->cache->delete('airship:' . $serial);

        return Http::status(200, $response);
    }
}