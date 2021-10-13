<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/09/2017
 * Time: 11:09
 */

namespace App\Controllers\Locations\MenuGenerator;

use App\Models\Locations\Informs\Inform;
use App\Models\Locations\LocationSettings;
use App\Models\PartnerBranding;
use App\Models\RadiusVendor;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class MenuGenerator
{
    protected $em;
    protected $connectCache;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function requestMenuRoute(Request $request, Response $response)
    {

        $serial = '';
        if ($request->getAttribute('serial') !== 'null') {
            $serial = $request->getAttribute('serial');
        }

        $send = $this->requestMenu($serial, $request->getAttribute('user'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function requestMenu(string $serial = '', array $user)
    {
        if (!empty($serial)) {
            $fetch = $this->connectCache->fetch($user['uid'] . ':menus:' . $serial);

            if (!is_bool($fetch)) {
                return Http::status(200, $fetch);
            }
        }

        $language = 'GB';

        $dashboardTitleValue          = MenuTranslator::getDashBoardTitle($language);
        $insightTitleValue            = MenuTranslator::getInsightTitle($language);
        $experienceTitleValue         = MenuTranslator::getExperienceTitle($language);
        $reviewsTitleValue            = MenuTranslator::getReviewTitle($language);
        $resourcesTitleValue          = MenuTranslator::getResourcesTitle($language);
        $resourcesChangelogTitleValue = MenuTranslator::getChangelogSubMenuTitle($language);

        $defaultMenu[] = [
            'title' => $dashboardTitleValue,
            'link'  => 'dashboard.home.overview',
            'key'   => 0,
            'sub'   => [
                [
                    'title' => MenuTranslator::getOverviewSubMenuTitle($language),
                    'link'  => 'dashboard.home.overview'
                ],
                [
                    'title' => MenuTranslator::getCustomersSubMenuTitle($language),
                    'link'  => 'dashboard.home.customers'
                ],
                [
                    'title' => MenuTranslator::getImpressionsSubMenuTitle($language),
                    'link'  => 'dashboard.home.impressions'
                ]
            ]
        ];


        $initialInsightStructure = [
            'title' => $insightTitleValue,
            'key'   => 2,
            'link'  => 'dashboard.location.insight',
            'style' => 'sl-graph',
            'sub'   => []
        ];

        if ($user['role'] <= 2 || $user['role'] === 5) {
            $initialInsightStructure['sub'] = [
                [
                    'title' => MenuTranslator::getCampaignsSubMenuTitle($language),
                    'link'  => 'dashboard.marketing.overview'
                ]
            ];
        }

        $defaultMenu[] = $initialInsightStructure;

        $pushToInsightsRef = array_search($insightTitleValue, array_column($defaultMenu, 'title'));

        if (($user['role'] === 3 || $user['role'] === 4) && empty($serial)) {
            array_splice($defaultMenu, $pushToInsightsRef, 1);
        }


        if ($user['role'] < 3 || $user['role'] === 5) {
            $defaultMenu[] = [
                'title' => $experienceTitleValue,
                'key'   => 3,
                'sub'   => [
                    [
                        'title' => MenuTranslator::getCampaignsSubMenuTitle($language),
                        'link'  => 'dashboard.marketing.automation.campaigns'
                    ]
                ]
            ];


            $pushToDashboardRef = array_search($dashboardTitleValue, array_column($defaultMenu, 'title'));

            $defaultMenu[$pushToDashboardRef]['sub'][] = [
                'title' => MenuTranslator::getLocationsSubMenuTitle($language),
                'link'  => 'dashboard.home.locations'
            ];
        }

        $pushToExperienceRef = array_search($experienceTitleValue, array_column($defaultMenu, 'title'));

        if ($user['role'] <= 2) {
            $defaultMenu[$pushToExperienceRef]['sub'][] = [
                'title' => MenuTranslator::getMarketeersSubMenuTitle($language),
                'link'  => 'dashboard.marketing.marketeer'
            ];

            $defaultMenu[] = [
                'title' => $reviewsTitleValue,
                'key'   => 4,
                'sub'   => [
                    [
                        'title' => MenuTranslator::getReviewSubmissionsSubTitle($language),
                        'link'  => 'dashboard.review.submissions'
                    ],
                    [
                        'title' => MenuTranslator::getReviewSetupSubTitle($language),
                        'link'  => 'dashboard.review.setup'
                    ]
                ]
            ];
        }

        if ($user['role'] <= 1) {
            $defaultMenu[] = [
                'title' => MenuTranslator::getPartnerTitle($language),
                'key'   => 5,
                'link'  => 'dashboard.partner',
                'sub'   => [
                    [
                        'title' => MenuTranslator::getQuotesSubMenuTitle($language),
                        'link'  => 'dashboard.partner.quotes'
                    ],
                    [
                        'title' => MenuTranslator::getSubscriptionsSubMenuTitle($language),
                        'link'  => 'dashboard.partner.subscriptions'
                    ]
                ]
            ];
        }

        $defaultMenu[] = [
            'title' => $resourcesTitleValue,
            'key'   => 6,
            'link'  => 'dashboard.resources',
            'sub'   => [
                [
                    'title' => $resourcesChangelogTitleValue,
                    'link'  => 'dashboard.resources.changelog'
                ]
            ]
        ];

        $pushToResourcesRef = array_search($resourcesTitleValue, array_column($defaultMenu, 'title'));

        $resourceStructure = [
            'title'    => MenuTranslator::getSupportSubMenuTitle($language),
            'link'     => 'https://get.stampede.help',
            'linkType' => 'outbound'
        ];

        $noSupportLink = false;

        if ($user['role'] > 2) {
            $partnerBranding = $this->em->getRepository(PartnerBranding::class)->findOneBy([
                'admin' => $user['admin']
            ]);

            $getChangeLogKey = array_search($resourcesChangelogTitleValue,
                array_column($defaultMenu[$pushToResourcesRef]['sub'], 'title'));

            array_splice($defaultMenu[$pushToResourcesRef]['sub'], $getChangeLogKey, 1);

            if (is_object($partnerBranding)) {
                if (array_key_exists('support', $partnerBranding->branding)) {
                    $resourceStructure['link'] = $partnerBranding->branding['support'];
                } else {
                    $noSupportLink = true;
                }
            }
        }

        if (!$noSupportLink) {
            $defaultMenu[$pushToResourcesRef]['sub'][] = $resourceStructure;
        }

        if (sizeof($defaultMenu[$pushToResourcesRef]['sub']) === 0) {
            array_splice($defaultMenu, $pushToResourcesRef, 1);
        }


        if (!empty($serial)) {

            if (is_bool($pushToExperienceRef)) {
                $defaultMenu[] = [
                    'title' => $experienceTitleValue,
                    'key'   => 3,
                    'sub'   => []
                ];

                $pushToExperienceRef = array_search($experienceTitleValue, array_column($defaultMenu, 'title'));
            }

            $getNetwork = $this->em->getRepository(LocationSettings::class)->findOneBy([
                'serial' => $serial
            ]);

            $captureValue = MenuTranslator::getCaptureSubMenuTitle($language);

            if ($user['role'] <= 3) {
                $defaultMenu[] = [
                    'title' => 'Wi-Fi',
                    'key'   => 1,
                    'sub'   => [
                        [
                            'title' => MenuTranslator::getGeneralSubMenuTitle($language),
                            'link'  => 'dashboard.location.wifi.general'
                        ],
                        [
                            'title' => MenuTranslator::getBusinessHoursSubMenuTitle($language),
                            'link'  => 'dashboard.location.wifi.schedule'
                        ]
                    ]
                ];

                $defaultMenu[$pushToExperienceRef]['sub'][] = [
                    'title' => $captureValue,
                    'link'  => 'dashboard.location.wifi.capture'
                ];
            }

            $pushToWifiMenuRef = array_search('Wi-Fi', array_column($defaultMenu, 'title'));


            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getOverviewSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.overview'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getRegistrationsSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.registration'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getPeopleSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.customer'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getConnectionsSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.connection'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getBandwidthSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.bandwidth'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => MenuTranslator::getDevicesSubMenuTitle($language),
                'link'  => 'dashboard.location.insight.device'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => 'Data Opt Out',
                'link'  => 'dashboard.location.insight.locationOptOut'
            ];

            $defaultMenu[$pushToInsightsRef]['sub'][] = [
                'title' => 'Marketing Opt Out',
                'link'  => 'dashboard.location.insight.marketingOptOut'
            ];


            if (in_array('Postcode', $getNetwork->freeQuestions)) {
                $defaultMenu[$pushToInsightsRef]['sub'][] = [
                    'title' => MenuTranslator::getUserOriginSubMenuTitle($language),
                    'link'  => 'dashboard.location.insight.origin'
                ];
            }

            if ($getNetwork->type === 1 || $getNetwork->type === 2) {
                if ($getNetwork->type === 1) {
                    foreach ($defaultMenu as $key => $item) {
                        if (!array_key_exists('sub', $item)) {
                            continue;
                        }

                        foreach ($item['sub'] as $k => $value) {
                            if ($value['title'] === $captureValue) {
                                array_splice($defaultMenu[$key]['sub'], $k, 1);
                            }
                        }
                    }
                }


                $defaultMenu[$pushToInsightsRef]['sub'][] = [
                    'title' => MenuTranslator::getPaymentsSubMenuTitle($language),
                    'link'  => 'dashboard.location.insight.payment'
                ];


                if ($user['role'] <= 3) {
                    $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                        'title' => MenuTranslator::getPaymentsSetUpSubMenuTitle($language),
                        'link'  => 'dashboard.location.wifi.payment',
                        'sub'   => [
                            [
                                'title' => 'Plans',
                                'link'  => 'dashboard.location.wifi.payment.plan'
                            ],
                            [
                                'title' => 'Methods',
                                'link'  => 'dashboard.location.wifi.payment.method'
                            ]
                        ]
                    ];
                }
            }

            if ($user['role'] <= 3) {
                $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                    'title' => MenuTranslator::getConnectedSubMenuTitle($language),
                    'link'  => 'dashboard.location.wifi.connected',
                    'style' => 'sl-users'
                ];

                $devicesTitleValue = MenuTranslator::getDevicesSubMenuTitle($language);

                $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                    'title' => $devicesTitleValue,
                    'link'  => 'dashboard.location.wifi.device'
                ];

                $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                    'title'  => MenuTranslator::getNetworkWifiSubMenuTitle($language),
                    'link'   => 'dashboard.location.wifi.network',
                    'method' => 'mikrotik'
                ];


                $defaultMenu[$pushToExperienceRef]['sub'][] = [
                    'title' => MenuTranslator::getBrandingSubMenuTitle($language),
                    'link'  => 'dashboard.location.branding',
                    'style' => 'sl-tag'
                ];

            }

            if ($user['role'] <= 2) {
                $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                    'title' => 'Data Sync',
                    'link'  => 'dashboard.location.wifi.sync'
                ];
            }

            if ($user['role'] === 0 || $user['role'] === 5 || $user['role'] === 2) {
                $defaultMenu[$pushToExperienceRef]['sub'][] = [
                    'title'  => MenuTranslator::getCreateCampaignSubMenuTitle($language),
                    'link'   => 'dashboard.marketing.automation.create',
                    'params' => [
                        'id'     => 'new',
                        'serial' => $serial
                    ],
                    'style'  => 'sl-bubbles'
                ];
            }

            if ($user['role'] <= 3) {
                $getVendor = $this->em->getRepository(Inform::class)->findOneBy([
                    'serial' => $serial
                ], [
                    'createdAt' => 'DESC'
                ]);

                $vendor = null;

                if (is_object($getVendor)) {
                    $vendor = $getVendor->vendor;
                } else {
                    $getVendor = $this->em->getRepository(RadiusVendor::class)->findOneBy([
                        'serial' => $serial
                    ],
                        [
                            'createdAt' => 'DESC'
                        ]);
                    if (is_object($getVendor)) {
                        $vendor = $getVendor->vendor;
                    }
                }

                if (!is_null($vendor)) {
                    $vendor = strtolower($vendor);

                    $notToDisplayConnectionSetting = ['unifi', 'meraki', 'mikrotik', 'ignitenet', 'ruckus-smartzone'];


                    if (is_object($getVendor) && (is_a($getVendor, RadiusVendor::class)) ||
                        $vendor === 'openmesh' || $vendor === 'ligowave') {
                        if ($vendor !== 'meraki') {
                            $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                                'title' => MenuTranslator::getRadiusSetupSubMenuTitle($language),
                                'link'  => 'dashboard.location.wifi.radius'
                            ];
                        }

                    } elseif (!in_array($vendor, $notToDisplayConnectionSetting)) {
                        $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                            'title' => ucfirst($vendor) . ' ' . MenuTranslator::getVendorConnectionSubMenuTitle($language),
                            'link'  => 'dashboard.location.wifi.' . $vendor
                        ];
                    }
                }

                if ($vendor !== 'mikrotik') {
                    foreach ($defaultMenu as $key => $item) {
                        if (!array_key_exists('sub', $item)) {
                            continue;
                        }

                        foreach ($item['sub'] as $k => $value) {
                            if (!array_key_exists('method', $value)) {
                                continue;
                            }

                            if ($vendor !== $value['method']) {
                                array_splice($defaultMenu[$key]['sub'], $k, 1);
                            }
                        }
                    }

                    $deviceRef = array_search($devicesTitleValue, array_column($defaultMenu[$pushToWifiMenuRef]['sub'],
                        'title'));
                    array_splice($defaultMenu[$pushToWifiMenuRef]['sub'], $deviceRef, 1);

                }
            }

            $defaultMenu[$pushToWifiMenuRef]['sub'][] = [
                'title' => MenuTranslator::getStatusCheckSubMenuTitle($language),
                'link'  => 'dashboard.location.wifi.debug'
            ];

            array_multisort(array_map(function ($element) {
                return $element['key'];
            }, $defaultMenu), SORT_ASC, $defaultMenu);

            $this->connectCache->save($user['uid'] . ':menus:' . $serial, $defaultMenu);
        }


        return Http::status(200, $defaultMenu);
    }
}
