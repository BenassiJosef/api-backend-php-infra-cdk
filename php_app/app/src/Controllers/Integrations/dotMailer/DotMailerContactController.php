<?php
/**
 * Created by jamieaitken on 01/10/2018 at 15:17
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\dotMailer;

use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use App\Utils\CacheEngine;
use Curl\Curl;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class DotMailerContactController
{
    protected $logger;
    protected $em;
    protected $filterListController;
    private $cache;
    private $resourceUrl = 'https://api.dotmailer.com/v2/address-books';

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
                $this->sendToDotMailer($details['apiKey'], $details['contactListId'], $userProfile['email'],
                    $userProfile['first'], $userProfile['last']);

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToDotMailer($details['apiKey'], $details['contactListId'], $userProfile['email'],
                    $userProfile['first'], $userProfile['last']);
            }
        }


        return Http::status(200);
    }

    public function sendToDotMailer(
        string $apiKey,
        string $groupId,
        string $email,
        ?string $firstName,
        ?string $lastName
    ) {
        $request     = new Curl();
        $authDetails = explode(';', $apiKey);
        $request->setBasicAuthentication($authDetails[0], $authDetails[1]);
        $request->setHeader('Content-Type', 'application/json');
        $request->post($this->resourceUrl . '/' . $groupId . '/contacts', [
            'email'      => $email,
            'dataFields' => [
                [
                    'key'   => 'FIRSTNAME',
                    'value' => isset($firstName) ? $firstName : ''
                ],
                [
                    'key'   => 'LASTNAME',
                    'value' => isset($lastName) ? $lastName : ''
                ],
                [
                    'key'   => 'FULLNAME',
                    'value' => (isset($firstName) ? $firstName : '') . ' ' . (isset($lastName) ? $lastName : '')
                ]
            ]
        ]);
    }
}