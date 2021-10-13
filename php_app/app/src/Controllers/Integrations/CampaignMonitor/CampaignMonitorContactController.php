<?php
/**
 * Created by jamieaitken on 10/10/2018 at 09:39
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\CampaignMonitor;

use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorListLocation;
use App\Models\Integrations\CampaignMonitor\CampaignMonitorUserDetails;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Package\Filtering\UserFilter;
use App\Utils\CacheEngine;
use Curl\Curl;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class CampaignMonitorContactController
{
    protected $em;
    protected $logger;
    protected $cache;
    private $resourceUrl = 'https://api.createsend.com/api/v3.2/subscribers/';
    private $filterListController;

    public function __construct(Logger $logger, EntityManager $em, FilterListController $filterListController)
    {
        $this->logger = $logger;
        $this->em    = $em;
        $this->filterListController = $filterListController;
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

        $getStatements = $this->filterListController->getStatements($locationIds);

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
                $this->sendToCampaignMonitor($details['apiKey'], $details['contactListId'],
                    $userProfile['email'],
                    $userProfile['first'],
                    $userProfile['last']);

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToCampaignMonitor($details['apiKey'], $details['contactListId'],
                    $userProfile['email'],
                    $userProfile['first'], $userProfile['last']);
            }
        }


        return Http::status(200);
    }

    public function sendToCampaignMonitor(
        string $apiKey,
        string $groupId,
        string $email,
        ?string $firstName,
        ?string $lastName
    ) {
        $request = new Curl();
        $request->setBasicAuthentication($apiKey);
        $request->setHeader('Content-Type', 'application/json');
        $response = $request->post($this->resourceUrl . $groupId . '.json', [
            'EmailAddress'   => $email,
            'Name'           => (isset($firstName) ? $firstName : '') . ' ' . (isset($lastName) ? $lastName : ''),
            'ConsentToTrack' => 'Yes'
        ]);
    }
}