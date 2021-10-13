<?php
/**
 * Created by jamieaitken on 21/05/2018 at 09:13
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;

use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class MultiSiteReportController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get(
            $request->getAttribute('user')['access'],
            $request->getAttribute('reportKind'),
            $request->getQueryParams());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(array $serials, string $reportKind, array $options)
    {

        $allowedKinds = [
            'branding',
            'general',
            'capture',
            'noconnections',
            'offlineactive',
            'splashimpressions',
            'splashimpressionsproactive',
            'serialsExceedingLimit',
            'topPerformingCustomers'
        ];

        if (!in_array($reportKind, $allowedKinds)) {
            return Http::status(204);
        }

        $reportCreator = new MultiSiteReportProducer($this->em);
        $report        = $reportCreator->createReport($reportKind);

        $data = $report->getData($serials, $options);

        if (empty($data)) {
            return Http::status(204);
        }

        return Http::status(200, $data);
    }
}