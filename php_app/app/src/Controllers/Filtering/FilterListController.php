<?php
/**
 * Created by jamieaitken on 03/10/2018 at 10:15
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Filtering;

use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\Organization;
use App\Package\Filtering\UserFilter;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class FilterListController
{
    protected $em;
    protected $userFilter;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger, EntityManager $em, UserFilter $userFilter)
    {
        $this->em                   = $em;
        $this->logger               = $logger;
        $this->userFilter           = $userFilter;
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('filterId'));
        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getAllRoute(Request $request, Response $response)
    {

        $type = $request->getQueryParam('type');
        if (!$type) {
            $type = 'question';
        }
        $send = $this->getAll($request->getAttribute('orgId'), $type);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete($request->getAttribute('filterId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateOrCreateRoute(Request $request, Response $response)
    {

        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->updateOrCreate($request->getParsedBody(), $organization);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function delete(string $id)
    {
        $filter = $this->em->getRepository(FilterEventList::class)->findOneBy(
            [
                'id' => $id
            ]
        );

        if (is_null($filter->uid)) {
            $this->logger->warning('Attempt to delete canned segment');

            return Http::status(403, 'Cannot edit canned segments');
        }

        $events = $this->em->getRepository(IntegrationEventCriteria::class)->findBy(
            [
                'filterListId' => $id
            ]
        );

        $this->em->remove($filter);
        foreach ($events as $event) {
            $this->em->remove($event);
        }

        $this->em->flush();

        return Http::status(200);
    }

    public function get(string $id)
    {
        $results = $this->userFilter->getFilter($id);
        if (is_null($results)) {
            return Http::status(204);
        }

        return Http::status(200, $results);
    }

    public function getAll(string $orgId, string $type)
    {

        $get = $this->em->createQueryBuilder()
                        ->select('u.id, u.name, u.type, u.uid')
                        ->from(FilterEventList::class, 'u')
                        ->where('u.organizationId = :orgId OR u.uid IS NULL')// uid of NULL indicates standard segmentation
                        ->setParameter('orgId', $orgId)
                        ->andWhere('u.type = :type')
                        ->setParameter('type', $type)
                        ->getQuery()
                        ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        $res = [];

        foreach ($get as $filter) {
            $filter['readOnly'] = false;
            if (is_null($filter['uid'])) {
                $filter['readOnly'] = true;
                if ($type === 'search') {
                    $res[] = $filter;
                }
            } else {
                $res[] = $filter;
            }
        }


        return Http::status(200, $res);
    }

    public function updateOrCreate(array $body, Organization $organization)
    {

        if (!isset($body['name'])) {
            return Http::status(400, 'REQUIRES_NAME');
        }

        if (!isset($body['id'])) {
            $newList = new FilterEventList($organization, $body['name'], $body['type']);
            $this->em->persist($newList);
        } else {
            $newList = $this->em->getRepository(FilterEventList::class)->findOneBy(
                [
                    'id' => $body['id']
                ]
            );

            if (is_null($newList)) {
                return Http::status(400, 'INVALID_ID');
            }

            // don't allow deletion of "canned" segments
            if (is_null(($newList->uid))) {
                $this->logger->warning('Attempt to edit canned segment');

                return Http::status(403, 'Cannot edit canned segments');
            }

            $newList->name = $body['name'];

            $oldEvents = $this->em->createQueryBuilder()
                                  ->select('u.id')
                                  ->from(IntegrationEventCriteria::class, 'u')
                                  ->where('u.filterListId = :filterId')
                                  ->setParameter('filterId', $newList->id)
                                  ->getQuery()
                                  ->getArrayResult();

            $oldEventsIds = [];
            $newEventIds  = [];
            $deleteEvents = [];
            foreach ($oldEvents as $event) {
                $oldEventsIds[] = $event['id'];
            }

            foreach ($body['events'] as $event) {
                if (!isset($event['id'])) {
                    continue;
                }

                $newEventIds[] = $event['id'];
            }

            foreach ($oldEventsIds as $eventId) {
                if (!in_array($eventId, $newEventIds)) {
                    $deleteEvents[] = $eventId;
                }
            }

            $this->em->createQueryBuilder()
                     ->delete(IntegrationEventCriteria::class, 'u')
                     ->where('u.id IN (:eventList)')
                     ->setParameter('eventList', $deleteEvents)
                     ->getQuery()
                     ->execute();
        }

        $response = [
            'events' => []
        ];


        foreach ($body['events'] as $event) {
            if (!isset($event['id'])) {
                $newEvent = new IntegrationEventCriteria(
                    $newList->id, $event['question'], $event['operand'],
                    $event['value'], $event['joinType'], $event['position']
                );
                $this->em->persist($newEvent);
                $response['events'][] = $newEvent->getArrayCopy();
            } else {
                $findEvent = $this->em->getRepository(IntegrationEventCriteria::class)->findOneBy(
                    [
                        'id' => $event['id']
                    ]
                );

                $findEvent->question = $event['question'];
                $findEvent->operand  = $event['operand'];
                $findEvent->value    = $event['value'];
                $findEvent->joinType = $event['joinType'];
                $findEvent->position = $event['position'];

                $response['events'][] = $findEvent->getArrayCopy();
            }
        }


        $this->em->flush();

        $response['id']       = $newList->id;
        $response['name']     = $newList->name;
        $response['type']     = $newList->type;
        $response['readOnly'] = false;


        return Http::status(200, $response);
    }

    function getStatements(array $locationIds)
    {
        return $this->em->createQueryBuilder()
                        ->select('i.question, i.operand, i.value, i.joinType, i.position, i.filterListId, i.id')
                        ->from(IntegrationEventCriteria::class, 'i')
                        ->leftJoin(FilterEventList::class, 'u', 'WITH', 'i.filterListId = u.id')
                        ->where('i.filterListId IN (:id)')
                        ->setParameter('id', $locationIds)
                        ->orderBy('i.position', 'ASC')
                        ->getQuery()
                        ->getArrayResult();
    }
}