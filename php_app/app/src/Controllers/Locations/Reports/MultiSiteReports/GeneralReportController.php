<?php
/**
 * Created by jamieaitken on 21/05/2018 at 09:24
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;


use App\Models\Locations\LocationSettings;
use App\Models\Locations\WiFi\LocationWiFi;
use Doctrine\ORM\EntityManager;

class GeneralReportController implements IMultiSiteReport
{

    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getData(array $serials, array $options)
    {
        $locations = $this->em->createQueryBuilder()
            ->select('u.serial, u.url, u.alias, w.ssid')
            ->from(LocationSettings::class, 'u')
            ->leftJoin(LocationWiFi::class, 'w', 'WITH', 'u.wifi = w.id')
            ->where('u.serial IN (:serials)')
            ->setParameter('serials', $serials)
            ->getQuery()
            ->getArrayResult();

        $returnStructure = [
            'averages' => [
                'hasAlias'    => 0,
                'noAlias'     => 0,
                'hasUrl'      => 0,
                'noUrl'       => 0,
                'defaultUrl'  => 0,
                'defaultSsid' => 0
            ],
            'totals'   => [
                'hasAlias'    => [],
                'noAlias'     => [],
                'hasUrl'      => [],
                'noUrl'       => [],
                'defaultUrl'  => [],
                'defaultSsid' => []
            ]
        ];

        foreach ($locations as $key => $location) {

            if (!is_null($location['alias']) && !empty($location['alias'])) {
                $returnStructure['totals']['hasAlias'] [] = [
                    'serial' => $location['serial']
                ];

            } else {
                $returnStructure['totals']['noAlias'][] = [
                    'serial' => $location['serial']
                ];
            }

            if (!is_null($location['url']) && !empty($location['url'])) {

                if (strpos($location['url'], 'blackbx') !== false) {
                    $returnStructure['totals']['defaultUrl'][] = [
                        'serial' => $location['serial']
                    ];
                } else {
                    $returnStructure['totals']['hasUrl'] [] = [
                        'serial' => $location['serial']
                    ];
                }
            } else {
                $returnStructure['totals']['noUrl'][] = [
                    'serial' => $location['serial']
                ];
            }

            if ($location['serial'] === $location['ssid']) {
                $returnStructure['totals']['defaultSsid'][] = [
                    'serial' => $location['serial']
                ];
            }
        }

        if ($returnStructure['totals']['hasAlias'] > 0) {
            $returnStructure['averages']['hasAlias'] = round((sizeof($returnStructure['totals']['hasAlias']) / sizeof($locations)) * 100);

        }

        if ($returnStructure['totals']['noAlias'] > 0) {
            $returnStructure['averages']['noAlias'] = round((sizeof($returnStructure['totals']['noAlias']) / sizeof($locations)) * 100);

        }

        if ($returnStructure['totals']['hasUrl'] > 0) {
            $returnStructure['averages']['hasUrl'] = round((sizeof($returnStructure['totals']['hasUrl']) / sizeof($locations)) * 100);

        }

        if ($returnStructure['totals']['noUrl'] > 0) {
            $returnStructure['averages']['noUrl'] = round((sizeof($returnStructure['totals']['noUrl']) / sizeof($locations)) * 100);

        }

        if ($returnStructure['totals']['defaultUrl'] > 0) {
            $returnStructure['averages']['defaultUrl'] = round((sizeof($returnStructure['totals']['defaultUrl']) / sizeof($locations)) * 100);

        }

        if ($returnStructure['totals']['defaultSsid'] > 0) {
            $returnStructure['averages']['defaultSsid'] = round((sizeof($returnStructure['totals']['defaultSsid']) / sizeof($locations)) * 100);

        }

        return $returnStructure;
    }
}