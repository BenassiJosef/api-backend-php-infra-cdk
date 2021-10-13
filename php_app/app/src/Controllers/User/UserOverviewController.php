<?php
/**
 * Created by jamieaitken on 12/03/2018 at 16:07
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\User;

use App\Package\Filtering\UnsupportedFilterOperation;
use App\Package\Filtering\UserFilter;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class UserOverviewController
{
    protected $em;
    protected $userFilter;

    public function __construct(EntityManager $em, UserFilter $userFilter)
    {
        $this->em = $em;
        $this->userFilter = $userFilter;
        $this->organisationProvider = new OrganizationProvider($em);
    }

    public function getRoute(Request $request, Response $response)
    {

        $offset = 0;
        $body = $request->getParsedBody();

        $filterId = null;
        unset($body['serial']);
        if (isset($body['offset'])) {
            $offset = $body['offset'];
            unset($body['offset']);
        }
        if (isset($body['filterId'])) {
            $filterId = $body['filterId'];
            unset($body['filterId']);
        }

        $orgId = $request->getAttribute('orgId');
        // get serials from getAttribute('orgId');
        // $serial = $request->getAttribute('user')['access'];
        $organization = $this->organisationProvider->organizationForRequest($request);
        $locations = iterator_to_array($organization->getLocations());
        $serial = [];
        foreach ($locations as $location) {
            $serial[] = $location->getSerial();
        }
        $send = $this->get($orgId, $serial, $offset, $body, $filterId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $orgId, array $serial, int $offset, array $search, string $filterId = null)
    {
        try {
            $filters = null;
            if ($filterId) {
                $filters = $this->userFilter->getFilter($filterId);
            }

            $query = $this->userFilter->getProfileQuery($orgId, $serial, $filters);
            $paginator = new Paginator($query, false);
            $totalItems = $this->userFilter->getTotalCount($orgId, $serial, $filters);

            $pageSize = 25;
            $pageCount = ceil($totalItems / $pageSize);

            $paginator
                ->getQuery()
                ->setFirstResult($pageSize * (max(1, $offset) - 1))
                ->setMaxResults($pageSize);

            $paginator->setUseOutputWalkers(false);
            $users = $paginator->getIterator()->getArrayCopy();

            // fix up the timestamps to be DateTime objects
            foreach ($users as $key => $user) {
                $users[$key]['timestamp'] = new \DateTime($user['timestamp']);
                $users[$key]['lastupdate'] = new \DateTime($user['lastupdate']);
            }

            if (empty($users)) {
                return Http::status(204, 'NO_USERS_FOUND');
            }
            $response = [
                'users' => $users,
                'totalUsers' => $totalItems,
                'pages' => $pageCount,
                'nextOffset' => $offset + 25,
                'hasMore' => false,
                'params' => $params ?? []
            ];

            if ($offset <= $response['totalUsers'] && count($users) !== $totalItems) {
                $response['hasMore'] = true;
            }

            return Http::status(200, $response);

        } catch (UnsupportedFilterOperation $e) {
            return HTTP::status(400, ["message" => $e->getMessage()]);
        }
    }
}