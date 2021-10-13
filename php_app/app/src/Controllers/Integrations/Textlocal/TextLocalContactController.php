<?php
/**
 * Created by jamieaitken on 26/09/2018 at 15:04
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Textlocal;


use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Models\Integrations\TextLocal\TextLocalContactListLocation;
use App\Models\Integrations\TextLocal\TextLocalUserDetails;
use App\Package\Filtering\UserFilter;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

class TextLocalContactController
{
    protected $logger;
    protected $em;
    protected $filterListController;
    private $cache;
    private $resourceUrl = 'https://api.txtlocal.com/create_contacts_bulk/';

    public function __construct(Logger $logger, EntityManager $em, FilterListController $filterListController)
    {
        $this->logger = $logger;
        $this->filterListController = $filterListController;
        $this->em    = $em;
        $this->cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function createContactRoute(Request $request, Response $response)
    {
        $send = $this->createContact($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createContact(array $userProfile, array $textLocalDetails)
    {
        $locationIds = [];

        foreach ($textLocalDetails as $locationId) {
            $locationIds[] = $locationId['filterListId'];
        }

        $getStatements = $this->filterListControler->getStatements($locationIds);

        $filter = new UserProfileFilter();

        $statements = [];

        foreach ($getStatements as $statement) {
            if (!isset($statements[$statement['filterListId']])) {
                $statements[$statement['filterListId']] = [
                    'statements' => [],
                ];
            }

            $statements[$statement['filterListId']]['statements'][] = $statement;
        }

        foreach ($textLocalDetails as $details) {

            if (!isset($details['apiKey'], $details['contactListId'])) {
                continue;
            }

            if (empty($statements[$details['filterListId']]['statements'])) {
                $this->sendToTextLocal($details['apiKey'], $details['contactListId'], $userProfile['phone'],
                    $userProfile['first'], $userProfile['last']);

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToTextLocal($details['apiKey'], $details['contactListId'], $userProfile['phone'],
                    $userProfile['first'], $userProfile['last']);
            }
        }


        return Http::status(200);
    }

    public function sendToTextLocal(
        string $apiKey,
        string $groupId,
        string $phoneNumber,
        ?string $firstName,
        ?string $lastName
    ) {
        $request = new Curl();
        $request->post($this->resourceUrl, [
            'apikey'   => $apiKey,
            'group_id' => $groupId,
            'contacts' => json_encode([
                [
                    'number'     => $phoneNumber,
                    'first_name' => isset($firstName) ? $firstName : '',
                    'last_name'  => isset($lastName) ? $lastName : ''
                ]
            ])
        ]);
    }
}