<?php
/**
 * Created by jamieaitken on 21/05/2018 at 11:24
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\Locations\LocationSettings;
use Doctrine\ORM\EntityManager;

class CaptureReportController implements IMultiSiteReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getData(array $serials, array $options)
    {
        $captureSites = $this->em->createQueryBuilder()
            ->select(
                '
                u.serial,
                u.freeQuestions,
                u.customQuestions
            ')->from(LocationSettings::class, 'u')
            ->where('u.serial IN (:serial)')
            ->setParameter('serial', $serials)
            ->getQuery()
            ->getArrayResult();

        $returnStructure = [
            'averages' => [
                'questions' => [
                    'Email'     => 0,
                    'Firstname' => 0,
                    'Lastname'  => 0,
                    'Phone'     => 0,
                    'Postcode'  => 0,
                    'Optin'     => 0,
                    'DoB'       => 0,
                    'Gender'    => 0,
                    'Country'   => 0
                ]
            ],
            'totals'   => [
                'tiers'     => [
                    'low'    => [],
                    'medium' => [],
                    'high'   => []
                ],
                'questions' => [
                    'Email'     => 0,
                    'Firstname' => 0,
                    'Lastname'  => 0,
                    'Phone'     => 0,
                    'Postcode'  => 0,
                    'Optin'     => 0,
                    'DoB'       => 0,
                    'Gender'    => 0,
                    'Country'   => 0
                ]
            ]
        ];

        foreach ($captureSites as $key => $site) {

            $questions = sizeof($site['freeQuestions']) + sizeof($site['customQuestions']);

            if ($questions >= 1 && $questions <= 2) {
                $returnStructure['totals']['tiers']['low'] [] = [
                    'serial' => $site['serial']
                ];
            } elseif ($questions >= 3 && $questions <= 4) {
                $returnStructure['totals']['tiers']['medium'] [] = [
                    'serial' => $site['serial']
                ];
            } elseif ($questions >= 5) {
                $returnStructure['totals']['tiers']['high'] [] = [
                    'serial' => $site['serial']
                ];
            }

            foreach ($site['freeQuestions'] as $k => $v) {
                $returnStructure['totals']['questions'][$v] += 1;
            }
        }

        if ($returnStructure['totals']['questions']['Email'] > 0) {
            $returnStructure['averages']['questions']['Email'] = round($returnStructure['totals']['questions']['Email'] / sizeof($captureSites) * 100);

        }

        if ($returnStructure['totals']['questions']['Firstname'] > 0) {
            $returnStructure['averages']['questions']['Firstname'] = round($returnStructure['totals']['questions']['Firstname'] / sizeof($captureSites) * 100);

        }

        if ($returnStructure['totals']['questions']['Lastname'] > 0) {
            $returnStructure['averages']['questions']['Lastname'] = round($returnStructure['totals']['questions']['Lastname'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['Phone'] > 0) {
            $returnStructure['averages']['questions']['Phone'] = round($returnStructure['totals']['questions']['Phone'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['Postcode'] > 0) {
            $returnStructure['averages']['questions']['Postcode'] = round($returnStructure['totals']['questions']['Postcode'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['Optin'] > 0) {
            $returnStructure['averages']['questions']['Optin'] = round($returnStructure['totals']['questions']['Optin'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['DoB'] > 0) {
            $returnStructure['averages']['questions']['DoB'] = round($returnStructure['totals']['questions']['DoB'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['Gender'] > 0) {
            $returnStructure['averages']['questions']['Gender'] = round($returnStructure['totals']['questions']['Gender'] / sizeof($captureSites) * 100);
        }

        if ($returnStructure['totals']['questions']['Country'] > 0) {
            $returnStructure['averages']['questions']['Country'] = round($returnStructure['totals']['questions']['Country'] / sizeof($captureSites) * 100);
        }

        return $returnStructure;
    }
}