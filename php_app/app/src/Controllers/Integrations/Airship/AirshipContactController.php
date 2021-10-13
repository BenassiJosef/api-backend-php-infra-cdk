<?php
/**
 * Created by jamieaitken on 2019-07-04 at 11:18
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Airship;


use App\Controllers\Filtering\FilterListController;
use App\Controllers\Integrations\UserProfileFilter;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Package\Filtering\UserFilter;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class AirshipContactController
{
    protected $logger;
    protected $em;
    protected $filterListController;

    public function __construct(Logger $logger, EntityManager $em, FilterListController $filterListController)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->filterListController = $filterListController;
    }

    public function createContact(array $userProfile, array $airshipDetails)
    {
        $locationIds = [];

        foreach ($airshipDetails as $locationId) {
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

        foreach ($airshipDetails as $details) {

            if (!isset($details['apiKey'], $details['contactListId'])) {
                continue;
            }

            if (empty($statements[$details['filterListId']]['statements'])) {
                $this->sendToAirship($details['apiKey'], $details['contactListId'], $userProfile);

                continue;
            }

            $passFilter = $filter->groupCriteriaMet($statements[$details['filterListId']]['statements'], $userProfile);

            if ($passFilter) {
                $this->sendToAirship($details['apiKey'], $details['contactListId'], $userProfile);
            }
        }


        return Http::status(200);
    }

    public function sendToAirship(string $key, string $groupId, array $userProfile)
    {
        $authDetails = explode(';', $key);

        $userProfile = $this->formatUserKeysToAirshipKeys($userProfile, $authDetails[2]);

        $wsdl   = "https://secure.airship.co.uk/SOAP/V3/Contact.wsdl";
        $client = new \SoapClient($wsdl);
        //username, password, source, name
        $client->createContact($authDetails[0], $authDetails[1], $userProfile, [
            $groupId
        ], [], [
            [
                "consenttypeid" => 1, "consentstatus" => "Y"
            ]
        ]);

    }

    private function formatUserKeysToAirshipKeys(array $userProfile, string $sourceId)
    {

        $map = [
            'first'    => 'firstname',
            'last'     => 'lastname',
            'gender'   => 'gender',
            'email'    => 'email',
            'phone'    => 'mobilenumber',
            'postcode' => 'postcode',
            'country'  => 'country'
        ];

        foreach ($userProfile as $key => $value) {

            if (is_null($value)) {
                unset($userProfile[$key]);
                continue;
            }

            if (isset($map[$key])) {

                if ($key === 'phone') {
                    $userProfile[$map[$key]] = str_replace('+44', '0', $value);
                } elseif ($key === 'gender') {
                    // only the M and F
                    $gender = strtoupper($value);
                    if ($gender === 'M' || $gender === 'F') {
                        $userProfile[$map[$key]] = $gender;
                    } else {
                        $userProfile[$map[$key]] = '';
                    }
                } else {
                    $userProfile[$map[$key]] = $userProfile[$key];
                }
                if ($map[$key] !== $key) {
                    unset($userProfile[$key]);
                }
            }
        }

        if (isset($userProfile['birthMonth'])) {

            $currentMonth = $userProfile['birthMonth'];
            $currentDay   = $userProfile['birthDay'];

            if (strlen($currentDay) < 2) {
                $currentDay = '0' . $currentDay;
            }

            if (strlen($currentMonth) < 2) {
                $currentMonth = '0' . $currentMonth;
            }

            $userProfile['dob'] = '1970-' . $currentMonth . '-' . $currentDay;
            unset($userProfile['birthDay'], $userProfile['birthMonth']);
        }

        $userProfile['sourceid']   = $sourceId;
        $userProfile['allowsms']   = 'Y';
        $userProfile['allowcall']  = 'Y';
        $userProfile['allowemail'] = 'Y';

        return $userProfile;
    }
}