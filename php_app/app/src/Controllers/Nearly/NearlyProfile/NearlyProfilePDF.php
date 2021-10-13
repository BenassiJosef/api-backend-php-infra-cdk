<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:28
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;


use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFDevices;
use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFLocation;
use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFLocationConnections;
use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFLocationTotals;
use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFMarketing;
use App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents\NearlyProfilePDFUser;
use App\Utils\Exporters\Download;
use Psr\Http\Message\ResponseInterface;

class NearlyProfilePDF extends NearlyProfileExport
{

    public function create(array $profile)
    {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator("Captive Ltd");
        $pdf->SetAuthor("Stampede");

        $pdf->SetTitle("Report for " . $profile['email']);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $details = new NearlyProfilePDFUser();
        $details->setHeader('Profile Details');
        $details->setContents($profile);
        $details->create();

        $location = new NearlyProfilePDFLocation();
        $location->setHeader('Location Details');
        $location->setContents($profile['locations']['list']);
        $html = $location->getHeader();
        $html .= $location->create();

        $locationConnections = new NearlyProfilePDFLocationConnections();
        $locationConnections->setHeader('Location Connections');
        $locationConnections->setContents($profile['locations']['list']);
        $html .= $locationConnections->getHeader();
        $html .= $locationConnections->create();

        $locationTotals = new NearlyProfilePDFLocationTotals();
        $locationTotals->setHeader('Location Totals');
        $locationTotals->setContents($profile);
        $html .= $locationTotals->getHeader();
        $html .= $locationTotals->create();


        $devices = new NearlyProfilePDFDevices();
        $devices->setHeader('Devices');
        $devices->setContents($profile);
        $html .= $devices->getHeader();
        $html .= $devices->create();

        $marketing = new NearlyProfilePDFMarketing();
        $marketing->setHeader('Marketing Data');
        $marketing->setContents($profile);
        $html .= $marketing->getHeader();
        $html .= $marketing->create();

        $pdf->SetFont('times', '', 14);

        $pdf->AddPage();

        $pdf->writeHTMLCell(0, '', '', '', $details->getHeader(), 0, 1, 0, true, '', true);
        $pdf->writeHTMLCell(80, '', '', '', $details->getContents()[0], 0, 0, 0, true, 'L', true);
        $pdf->writeHTMLCell(80, '', '', '', $details->getContents()[1], 0, 1, 0, true, 'L', true);

        $pdf->writeHTMLCell(0, '', '', '', $html, 0, 1, 0, true, '', true);

        return $pdf->Output($profile['id'] . '.pdf', 'S');
    }

    public function download(string $filename, string $contents, ResponseInterface $response, string $profileId)
    {
        $download = new Download();

        return $download->generateDownload('NearlyOnlineProfileExport', $contents, 'pdf', $response, $profileId);
    }
}