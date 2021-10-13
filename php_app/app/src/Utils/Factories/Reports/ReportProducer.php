<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 17/09/2017
 * Time: 22:12
 */

namespace App\Utils\Factories\Reports;

class ReportProducer
{
    private $reportFactory;

    public function __construct(ReportFactory $reportFactory)
    {
        $this->reportFactory = $reportFactory;
    }

    public function produce(string $reportKind, array $additionalParams)
    {
        $report = $this->reportFactory->createReport($reportKind);
        $report->setDefaultOrder($additionalParams);

        return $report;
    }


}
