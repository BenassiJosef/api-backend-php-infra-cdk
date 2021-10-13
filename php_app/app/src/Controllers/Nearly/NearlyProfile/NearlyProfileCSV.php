<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:51
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;


use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVDevices;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVLocation;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVLocationConnections;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVMarketing;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVMarketingEvents;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents\NearlyProfileCSVUser;
use App\Utils\Exporters\Download;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Writer\XLSX\Writer;
use Psr\Http\Message\ResponseInterface;

class NearlyProfileCSV extends NearlyProfileExport
{
    public function create(array $profile)
    {
        $writer = new Writer();
        $writer->setGlobalFunctionsHelper(new GlobalFunctionsHelper());

        $writer->openToFile('Nearly Online Profile Export.csv');

        $userSheet = $writer->addNewSheetAndMakeItCurrent();
        $userSheet->setName('User Details');

        $details = new NearlyProfileCSVUser();
        $details->setHeaders([
            'Name',
            'Email',
            'Gender',
            'Phone',
            'Age',
            'Postcode',
            'Postcode Valid',
            'Birth Month',
            'Birth Day',
            'Verified'
        ]);
        $writer->addRow($details->getHeaders());
        $details->setContents($profile);
        $details->create();

        $writer->addRow($details->getContents());

        $locationSheet = $writer->addNewSheetAndMakeItCurrent();
        $locationSheet->setName('Locations');

        $location = new NearlyProfileCSVLocation();
        $location->setHeaders([
            'Location',
            'Downloaded',
            'Uploaded',
            'Up Time',
            'Logins',
            'Opt Out'
        ]);

        $writer->addRow($location->getHeaders());
        $location->setContents($profile['locations']['list']);
        $location->create();

        foreach ($location->getContents() as $locationItem) {
            $writer->addRow($locationItem);
        }

        $locationConnectionsSheet = $writer->addNewSheetAndMakeItCurrent();
        $locationConnectionsSheet->setName('Location Connection History');

        $locationConnections = new NearlyProfileCSVLocationConnections();
        $locationConnections->setHeaders([
            'Location',
            'Downloaded',
            'Uploaded',
            'Up Time',
            'Connected At',
            'Last Seen At'
        ]);
        $writer->addRow($locationConnections->getHeaders());
        $locationConnections->setContents($profile['locations']['list']);
        $locationConnections->create();

        $writer->addRows($locationConnections->getContents());

        $devicesSheet = $writer->addNewSheetAndMakeItCurrent();
        $devicesSheet->setName('Devices');

        $devices = new NearlyProfileCSVDevices();
        $devices->setHeaders([
            'MAC Address',
            'Downloaded',
            'Uploaded',
            'Browser Name',
            'Browser Version',
            'Device Brand',
            'Device Model'
        ]);
        $devices->setContents($profile);
        $devices->create();

        $writer->addRow($devices->getHeaders());

        $writer->addRows($devices->getContents());


        $marketingSheet = $writer->addNewSheetAndMakeItCurrent();
        $marketingSheet->setName('Marketing Data');

        $marketing = new NearlyProfileCSVMarketing();
        $marketing->setHeaders([
            'Location',
            'Emails Received',
            'SMS Received'
        ]);
        $marketing->setContents($profile);
        $writer->addRow($marketing->getHeaders());
        $marketing->create();
        $writer->addRows($marketing->getContents());


        $marketingEventsSheet = $writer->addNewSheetAndMakeItCurrent();
        $marketingEventsSheet->setName('Marketing Event Data');

        $marketingEvents = new NearlyProfileCSVMarketingEvents();
        $marketingEvents->setHeaders([
            'Location',
            'Type',
            'Sent'
        ]);
        $marketingEvents->setContents($profile);
        $writer->addRow($marketingEvents->getHeaders());
        $marketingEvents->create();

        $writer->addRows($marketingEvents->getContents());


        $writer->close();

        $fileString = file_get_contents('Nearly Online Profile Export.csv');

        return $fileString;
    }

    public function download(string $filename, string $contents, ResponseInterface $response, string $profileId)
    {
        $newDownload = new Download();

        return $newDownload->generateDownload('NearlyOnlineProfileExport', $contents, 'csv', $response, $profileId);
    }
}