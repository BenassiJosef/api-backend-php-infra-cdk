<?php

/**
 * Created by jamieaitken on 28/09/2018 at 14:10
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\MailChimp;

use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use App\Utils\CacheEngine;
use Curl\Curl;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;
use App\Controllers\Integrations\MailChimp\MailChimpSetupController;

class MailChimpContactController
{
    protected $logger;
    protected $em;
    protected $filterListController;
    private $cache;
    private $resourceUrl = '';

    public function __construct(Logger $logger, EntityManager $em, FilterListController $filterListController)
    {
        $this->logger = $logger;
        $this->filterListController = $filterListController;
        $this->em    = $em;
        $this->cache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function createRoute(Request $request, Response $response)
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
                $this->sendToMailChimp(
                    $details['apiKey'],
                    $details['contactListId'],
                    $userProfile['email'],
                    $userProfile['first'],
                    $userProfile['last'],
                    $userProfile['phone']
                );

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToMailChimp(
                    $details['apiKey'],
                    $details['contactListId'],
                    $userProfile['email'],
                    $userProfile['first'],
                    $userProfile['last'],
                    $userProfile['phone']
                );
            }
        }


        return Http::status(200);
    }

    public function sendToMailChimp(
        string $apiKey,
        string $groupId,
        string $emailAddress,
        ?string $firstName,
        ?string $lastName,
        ?string $phoneNumber
    ) {

        $mailchimp = new MailChimpSetupController($this->em);
        $this->resourceUrl = $mailchimp->getResourceUrl($apiKey);

        $this->resourceUrl = $this->resourceUrl . '/' . $groupId . '/members';

        $payload = [
            'email_address' => $emailAddress,
            'status'        => 'subscribed',
            'merge_fields'  => (object) [
                'FNAME' => isset($firstName) ? $firstName : 'N/A',
                'LNAME' => isset($lastName) ? $lastName : 'N/A',
                'PHONE' => isset($phoneNumber) ? $phoneNumber : ''
            ]
        ];


        $request = new Curl();
        $request->setBasicAuthentication('user', $apiKey);
        $request->setHeader('Content-Type', 'application/json');
        $request->post($this->resourceUrl, $payload);
    }
}
