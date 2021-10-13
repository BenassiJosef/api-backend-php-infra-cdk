<?php
/**
 * Created by jamieaitken on 21/05/2018 at 09:20
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Controllers\Nearly\NearlyImpressionController;
use Doctrine\ORM\EntityManager;

class MultiSiteReportProducer
{

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createReport(string $reportKind)
    {
        $report = null;

        switch ($reportKind) {
            case 'branding':
                $report = new BrandingReportController($this->em);
                break;
            case 'general':
                $report = new GeneralReportController($this->em);
                break;
            case 'capture':
                $report = new CaptureReportController($this->em);
                break;
            case 'noconnections':
                $report = new InformingSitesNoConnectionsReport($this->em);
                break;
            case 'offlineactive':
                $report = new OfflineActiveSitesReport($this->em);
                break;
            case 'splashimpressions':
                $report = new NearlyImpressionController($this->em);
                break;
            case 'splashimpressionsproactive':
                $report = new NearlyImpressionsMasterController($this->em);
                break;
            case 'serialsExceedingLimit':
                $report = new ConnectionLimitExceededController($this->em);
                break;
            case 'topPerformingCustomers':
                $report = new TopPerformingCustomersController($this->em);
                break;
        }

        return $report;
    }
}