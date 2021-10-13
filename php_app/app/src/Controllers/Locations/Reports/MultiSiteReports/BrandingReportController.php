<?php
/**
 * Created by jamieaitken on 18/05/2018 at 16:29
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\MultiSiteReports;

use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationSettings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class BrandingReportController implements IMultiSiteReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function getData(array $serials, array $options)
    {
        $brandingSites = $this->em->createQueryBuilder()
            ->select('
            u.boxShadow,
            u.headerLogoPadding,
            u.headerTopRadius,
            u.backgroundImage,
            u.headerImage,
            u.roundFormTopLeft,
            u.roundFormTopRight,
            u.roundFormBottomLeft,
            u.roundFormBottomRight,
            u.roundInputs,
            u.message,
            u.customCSS,
            p.serial
            ')
            ->from(LocationBranding::class, 'u')
            ->leftJoin(LocationSettings::class, 'p', 'WITH', 'u.id = p.branding')
            ->where('p.serial IN (:serial)')
            ->setParameter('serial', $serials)
            ->getQuery()
            ->getArrayResult();

        $returnStructure = [
            'averages' => [
                'shadowEnabled'     => 0,
                'headerLogoPadding' => 0,
                'headerTopRadius'   => 0,
                'backgroundImage'   => 0,
                'defaultBackground' => 0,
                'headerImage'       => 0,
                'defaultHeader'     => 0,
                'roundTopLeft'      => 0,
                'roundTopRight'     => 0,
                'roundBottomLeft'   => 0,
                'roundBottomRight'  => 0,
                'roundInputs'       => 0,
                'message'           => 0,
                'customCss'         => 0
            ],
            'totals'   => [
                'shadowEnabled'     => [],
                'headerLogoPadding' => [],
                'headerTopRadius'   => [],
                'backgroundImages'  => [],
                'defaultBackground' => [],
                'headerImages'      => [],
                'defaultHeader'     => [],
                'roundTopLeft'      => [],
                'roundTopRight'     => [],
                'roundBottomLeft'   => [],
                'roundBottomRight'  => [],
                'roundInputs'       => [],
                'message'           => [],
                'customCss'         => []
            ]
        ];

        foreach ($brandingSites as $site) {
            if ($site['boxShadow']) {
                $returnStructure['totals']['shadowEnabled'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['headerLogoPadding']) {
                $returnStructure['totals']['headerLogoPadding'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['headerTopRadius']) {
                $returnStructure['totals']['headerTopRadius'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if (!is_null($site['backgroundImage']) && !empty($site['backgroundImage'])) {
                $returnStructure['totals']['backgroundImages'] [] = [
                    'serial' => $site['serial']
                ];

                if (strpos($site['backgroundImage'], 'default') !== false) {
                    $returnStructure['totals']['defaultHeader'] [] = [
                        'serial' => $site['serial']
                    ];
                }
            }

            if (!is_null($site['headerImage']) && !empty($site['headerImage'])) {
                $returnStructure['totals']['headerImages'] [] = [
                    'serial' => $site['serial']
                ];

                if (strpos($site['headerImage'], 'default') !== false) {
                    $returnStructure['totals']['defaultBackground'][] = [
                        'serial' => $site['serial']
                    ];
                }
            }

            if ($site['roundFormTopLeft']) {
                $returnStructure['totals']['roundTopLeft'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['roundFormTopRight']) {
                $returnStructure['totals']['roundTopRight'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['roundFormBottomLeft']) {
                $returnStructure['totals']['roundBottomLeft'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['roundFormBottomRight']) {
                $returnStructure['totals']['roundBottomRight'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if ($site['roundInputs']) {
                $returnStructure['totals']['roundInputs'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if (!is_null($site['message']) && !empty($site['message'])) {
                $returnStructure['totals']['message'] [] = [
                    'serial' => $site['serial']
                ];
            }

            if (!is_null($site['customCSS']) && !empty($site['customCSS'])) {
                $returnStructure['totals']['customCss'] [] = [
                    'serial' => $site['serial']
                ];
            }
        }


        if (sizeof($returnStructure['totals']['shadowEnabled']) > 0) {
            $returnStructure['averages']['shadowEnabled'] = round((sizeof($returnStructure['totals']['shadowEnabled']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['headerLogoPadding']) > 0) {
            $returnStructure['averages']['headerLogoPadding'] = round((sizeof($returnStructure['totals']['headerLogoPadding']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['headerTopRadius']) > 0) {
            $returnStructure['averages']['headerTopRadius'] = round((sizeof($returnStructure['totals']['headerTopRadius']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['backgroundImages']) > 0) {
            $returnStructure['averages']['backgroundImages'] = round((sizeof($returnStructure['totals']['backgroundImages']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['defaultBackground']) > 0) {
            $returnStructure['averages']['defaultBackground'] = round((sizeof($returnStructure['totals']['defaultBackground']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['headerImages']) > 0) {
            $returnStructure['averages']['headerImages'] = round((sizeof($returnStructure['totals']['headerImages']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['defaultHeader']) > 0) {
            $returnStructure['averages']['defaultHeader'] = round((sizeof($returnStructure['totals']['defaultHeader']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['roundTopLeft']) > 0) {
            $returnStructure['averages']['roundTopLeft'] = round((sizeof($returnStructure['totals']['roundTopLeft']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['roundTopRight']) > 0) {
            $returnStructure['averages']['roundTopRight'] = round((sizeof($returnStructure['totals']['roundTopRight']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['roundBottomLeft']) > 0) {
            $returnStructure['averages']['roundBottomLeft'] = round((sizeof($returnStructure['totals']['roundBottomLeft']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['roundBottomRight']) > 0) {
            $returnStructure['averages']['roundBottomRight'] = round((sizeof($returnStructure['totals']['roundBottomRight']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['roundInputs']) > 0) {
            $returnStructure['averages']['roundInputs'] = round((sizeof($returnStructure['totals']['roundInputs']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['message']) > 0) {
            $returnStructure['averages']['message'] = round((sizeof($returnStructure['totals']['message']) / sizeof($brandingSites)) * 100);
        }

        if (sizeof($returnStructure['totals']['customCss']) > 0) {
            $returnStructure['averages']['customCss'] = round((sizeof($returnStructure['totals']['customCss']) / sizeof($brandingSites)) * 100);
        }

        return Http::status(200, $returnStructure);
    }
}