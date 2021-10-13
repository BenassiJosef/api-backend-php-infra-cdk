<?php
/**
 * Created by jamieaitken on 09/10/2018 at 11:18
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\ConstantContact;

use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use Curl\Curl;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class ConstantContactController
{
    protected $em;
    protected $logger;
    protected $filterListController;
    private $resourceUrl = 'https://api.constantcontact.com/v2/contacts';

    public function __construct(Logger $logger, EntityManager $em, FilterListController $filterListController)
    {
        $this->logger = $logger;
        $this->em = $em;
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

            if (!isset($details['accessToken'], $details['contactListId'])) {
                continue;
            }

            if (empty($statements[$details['filterListId']]['statements'])) {
                $this->sendToConstantContact($details['accessToken'], $details['contactListId'], $userProfile['phone'],
                    $userProfile['email'],
                    $userProfile['first'],
                    $userProfile['last']);

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToConstantContact($details['accessToken'], $details['contactListId'], $userProfile['phone'],
                    $userProfile['email'],
                    $userProfile['first'], $userProfile['last']);
            }
        }


        return Http::status(200);
    }

    public function sendToConstantContact(
        string $accessToken,
        string $groupId,
        string $phoneNumber,
        string $email,
        ?string $firstName,
        ?string $lastName
    ) {
        $request = new Curl();
        $request->setHeader('Authorization', 'Bearer ' . $accessToken);
        $request->post($this->resourceUrl . '?apikey=' . ConstantContactAuthorize::$apiKey, [
            'email_addresses' => [
                [
                    'email_address' => $email
                ]
            ],
            'lists'           => [
                [
                    'id' => $groupId
                ]
            ],
            'first_name'      => isset($firstName) ? $firstName : '',
            'last_name'       => isset($lastName) ? $lastName : '',
            'cell_phone'      => $phoneNumber
        ]);
    }
}