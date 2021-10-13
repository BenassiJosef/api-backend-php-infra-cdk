<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 17/09/2017
 * Time: 22:08
 */

namespace App\Utils\Factories\Reports;

use App\Controllers\Locations\Reports\_ConnectionReportController;
use App\Controllers\Locations\Reports\_CustomerReportController;
use App\Controllers\Locations\Reports\_DeviceReportController;
use App\Controllers\Locations\Reports\_PaymentsReportController;
use App\Controllers\Locations\Reports\_RegistrationReportController;
use App\Controllers\Locations\Reports\BrowserReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeConnectionReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeCustomerReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeDeviceReportController;
use App\Controllers\Locations\Reports\FakeReports\FakePaymentsReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeRegistrationsReportController;
use App\Controllers\Locations\Reports\GDPRReportController;
use App\Controllers\Locations\Reports\MarketingDeliverableReportController;
use App\Controllers\Locations\Reports\MarketingOptOutReportController;
use App\Controllers\Locations\Reports\NearlyStoriesPageReport;
use App\Controllers\Locations\Reports\NearlyStoriesReport;
use App\Controllers\Locations\Reports\OSReportController;
use App\Controllers\Locations\Reports\SplashScreenImpressions;
use Doctrine\ORM\EntityManager;

class ReportFactory
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
            case 'registrations':
                $report = new _RegistrationReportController($this->em);
                break;
            case 'registrationsFake':
                $report = new FakeRegistrationsReportController($this->em);
                break;
            case 'payments':
                $report = new _PaymentsReportController($this->em);
                break;
            case 'paymentsFake':
                $report = new FakePaymentsReportController($this->em);
                break;
            case 'devices':
                $report = new _DeviceReportController($this->em);
                break;
            case 'devicesFake':
                $report = new FakeDeviceReportController($this->em);
                break;
            case 'customer':
                $report = new _CustomerReportController($this->em);
                break;
            case 'customerFake':
                $report = new FakeCustomerReportController($this->em);
                break;
            case 'connections':
                $report = new _ConnectionReportController($this->em);
                break;
            case 'connectionsFake':
                $report = new FakeConnectionReportController($this->em);
                break;
            case 'gdpr':
                $report = new GDPRReportController($this->em);
                break;
            case 'marketingOptOut':
                $report = new MarketingOptOutReportController($this->em);
                break;
            case 'browser':
                $report = new BrowserReportController($this->em);
                break;
            case 'os':
                $report = new OSReportController($this->em);
                break;
            case 'splashimpressions':
                $report = new SplashScreenImpressions($this->em);
                break;
            case 'nearlystories':
                $report = new NearlyStoriesReport($this->em);
                break;
            case 'marketingdeliverable':
                $report = new MarketingDeliverableReportController($this->em);
                break;
        }

        return $report;
    }
}
