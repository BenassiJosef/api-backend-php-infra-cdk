<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/06/2017
 * Time: 11:41
 */

namespace App\Controllers\Marketing\Report;

use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Models\Marketing\MarketingDeliverable;
use App\Models\Marketing\MarketingDeliverableEvent;
use App\Models\MarketingCampaignEvents;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEvents;
use App\Models\MarketingLocations;
use App\Models\UserData;
use App\Models\UserRegistration;
use App\Package\Organisations\OrganisationIdProvider;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class _MarketingReportController
{
    protected $em;
    protected $newUploadController;
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->newUploadController = new _UploadStorageController($this->em);
        $this->orgIdProvider       = new OrganisationIdProvider($this->em);
    }

    public function overviewRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');

        $campaignId  = null;
        $serial      = null;
        $offset      = null;
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['campaignId'])) {
            $campaignId = $queryParams['campaignId'];
        }

        if (isset($queryParams['serial'])) {
            $serial = $queryParams['serial'];
        }

        if (isset($queryParams['offset'])) {
            $offset = intval($queryParams['offset']);
        }

        $send = $this->loadOverview($orgId, $campaignId, $serial, $offset);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadCampaignInfoRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $export      = false;

        $offset     = 0;
        $campaignId = null;

        $user = $request->getAttribute('accessUser');

        if (isset($queryParams['serial'])) {
            $serial = $queryParams['serial'];
        } else {
            $serial = $user['access'];
        }

        if (isset($queryParams['campaignId'])) {
            $campaignId = $queryParams['campaignId'];
        }

        if (isset($queryParams['export'])) {
            if ($queryParams['export'] === 'true') {
                $export = true;
            }
        }

        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        $send = $this->loadCampaignInfo(
            $serial,
            $queryParams['start'],
            $queryParams['end'],
            $export,
            $user['uid'],
            $offset,
            $campaignId
        );

        return $response->withJson($send, $send['status']);
    }

    private function loadOverview(string $orgId, $campaignId, $serial, $offset)
    {
        $sixMonthsAgoTime = new \DateTime();

        /**
         * Get all Campaigns belonging to user
         *
         * OPTIONAL
         *    Campaign Id: Gets a Specific Campaign
         */

        $getCampaigns = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(MarketingCampaigns::class, 'u');
        if (!is_null($serial)) {
            $getCampaigns = $getCampaigns->join(MarketingLocations::class, 's', 'WITH', 'u.id = s.campaignId');
        }
        $getCampaigns = $getCampaigns
            ->where('u.organizationId = :orgId')
            ->andWhere('u.deleted = :false')
            ->setParameter('false', false)
            ->setParameter('orgId', $orgId);

        if (!is_null($serial)) {
            $getCampaigns = $getCampaigns->andWhere('s.serial = :s')
                ->setParameter('s', $serial);
        }

        if (!is_null($campaignId)) {

            if (strpos($campaignId, 'REVIEW_') !== false) {
                $getCampaigns->andWhere('u.name = :id')
                    ->setParameter('id', $campaignId);
            } else {
                $getCampaigns->andWhere('u.id = :id')
                    ->setParameter('id', $campaignId);
            }
        }
        if (!is_null($offset)) {
            $getCampaigns = $getCampaigns->orderBy('u.created', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults(10);
            $results      = new Paginator($getCampaigns);
            $results->setUseOutputWalkers(false);
            $getCampaigns = $results->getIterator()->getArrayCopy();
        } else {
            $getCampaigns = $getCampaigns->getQuery()->getArrayResult();
        }

        if (empty($getCampaigns)) {
            return Http::status(204);
        }

        /**
         * Iterates through results making an array of campaign ids
         */

        $campaigns = [];
        foreach ($getCampaigns as $result) {
            if (!isset($campaigns[$result['id']])) {
                $campaigns[] = $result['id'];
            }
        }


        /**
         * Gets all the Marketing Events belonging to those campaign ids within the last six months
         *
         * OPTIONAL
         *    Serial: at a specific location
         */

        $query = $this->em->createQueryBuilder()
            ->select("
            u.id,
            u.type,
            u.profileId,
            u.serial,
            u.campaignId,
            i.spendPerHead,
            u.createdAt,
            YEAR(u.createdAt) as year,
            MONTH(u.createdAt) as month,
            a.event,
            a.eventSpecificInfo,
            a.timestamp,
            DAY(u.createdAt) as day")
            ->from(MarketingDeliverable::class, 'u')
            ->leftJoin(MarketingDeliverableEvent::class, 'a', 'WITH', 'u.id = a.marketingDeliverableId')
            ->join(MarketingCampaigns::class, 'i', 'WITH', 'u.campaignId = i.id')
            ->leftJoin(MarketingCampaignEvents::class, 'l', 'WITH', 'i.eventId = l.id')
            ->where('u.campaignId IN (:ids)')
            ->setParameter('ids', $campaigns);

        if (!is_null($serial)) {
            $query = $query->andWhere('u.serial = :serial')
                ->setParameter('serial', $serial);
        }

        $query = $query->andWhere('u.createdAt >= :sixMonths')
            ->setParameter('sixMonths', $sixMonthsAgoTime->modify('- 6 month'))
            ->getQuery()
            ->getArrayResult();


        $queryResponse = [];

        foreach ($query as $marketingEvent) {
            if (!isset($queryResponse[$marketingEvent['id']])) {
                $queryResponse[$marketingEvent['id']] = [
                    'type'         => $marketingEvent['type'],
                    'profileId'    => $marketingEvent['profileId'],
                    'serial'       => $marketingEvent['serial'],
                    'campaignId'   => $marketingEvent['campaignId'],
                    'createdAt'    => $marketingEvent['createdAt'],
                    'year'         => $marketingEvent['year'],
                    'month'        => $marketingEvent['month'],
                    'day'          => $marketingEvent['day'],
                    'spendPerHead' => $marketingEvent['spendPerHead'],
                    'events'       => []
                ];
            }

            $queryResponse[$marketingEvent['id']]['events'][] = [
                'event'             => $marketingEvent['event'],
                'eventSpecificInfo' => $marketingEvent['eventSpecificInfo'],
                'timestamp'         => $marketingEvent['timestamp']
            ];
        }

        /**
         * Calculates the different types of conversions
         */

        $getConversions = $this->em->createQueryBuilder()
            ->select("(COUNT(DISTINCT udsms.profileId) + COUNT(DISTINCT udemail.profileId)) as uniqueReturns,
            COUNT(DISTINCT udsms.profileId) as smsReturns,
            COUNT(DISTINCT udemail.profileId) as emailReturns,
            me.createdAt,
            me.campaignId,
            YEAR(me.createdAt) as year,
            MONTH(me.createdAt) as month")
            ->from(MarketingDeliverable::class, 'me')
            ->leftJoin(
                MarketingDeliverable::class,
                "mesms",
                "WITH",
                "mesms.id = me.id AND me.type='sms'"
            )
            ->leftJoin(
                MarketingDeliverable::class,
                "meemail",
                "WITH",
                "meemail.id = me.id AND me.type='email'"
            )
            ->leftJoin(
                UserRegistration::class,
                "udemail",
                "WITH",
                "udemail.profileId = me.profileId 
                AND udemail.serial = me.serial 
                AND me.type ='email' 
                AND (udemail.lastSeenAt >= DATE_ADD(me.createdAt, 1, 'DAY'))"
            )
            ->leftJoin(
                UserRegistration::class,
                "udsms",
                "WITH",
                "udsms.profileId = me.profileId
                 AND udsms.serial = me.serial
                 AND me.type = 'sms' 
                 AND (udsms.lastSeenAt >= DATE_ADD(me.createdAt, 1, 'DAY'))"
            )->where('me.campaignId IN (:campaigns)')
            ->andWhere("me.createdAt >= DATE_SUB(NOW(), 6, 'MONTH')")
            ->setParameter('campaigns', $campaigns)
            ->groupBy('me.campaignId')
            ->addGroupBy('year')
            ->addGroupBy('month')
            ->getQuery()
            ->getArrayResult();


        /**
         * Generate Response Structure
         */

        $response = [
            'totals'    => [
                'roi'            => 0,
                'spendPerHead'   => 0,
                'smsSent'        => 0,
                'emailSent'      => 0,
                'smsDelivered'   => 0,
                'emailDelivered' => 0,
                'sent'           => 0,
                'delivered'      => 0,
                'bounce'         => 0,
                'deferred'       => 0,
                'click'          => 0,
                'credits'        => 0,
                'conversion'     => 0,
                'uniqueReturns'  => 0,
                'smsReturns'     => 0,
                'emailReturns'   => 0,
                'opens'          => 0,
                'charts'         => $this->generateMonths(7)
            ],
            'campaigns' => []
        ];

        $tempConversionsKeyedByCampaignId = [];
        $tempConversionsKeyedByUnix       = [];

        foreach ($campaigns as $campaign) {
            if (!isset($response['campaigns'][$campaign])) {
                $response['campaigns'][$campaign] = [
                    'totals' => [
                        'roi'            => 0,
                        'smsSent'        => 0,
                        'emailSent'      => 0,
                        'smsDelivered'   => 0,
                        'emailDelivered' => 0,
                        'sent'           => 0,
                        'delivered'      => 0,
                        'bounce'         => 0,
                        'deferred'       => 0,
                        'click'          => 0,
                        'credits'        => 0,
                        'conversion'     => 0,
                        'uniqueReturns'  => 0,
                        'smsReturns'     => 0,
                        'emailReturns'   => 0,
                        'spendPerHead'   => 0,
                        'opens'          => 0
                    ],
                    'charts' => $this->generateMonths(7)
                ];
            }
        }


        foreach ($getConversions as $key => $conversion) {
            $date = new \DateTime($conversion['createdAt']->date);
            $date->modify('first day of this month');
            $conversion['unix'] = $date->getTimestamp();
            foreach ($conversion as $ke => $value) {
                if (is_numeric($value)) {
                    $getConversions[$key][$ke] = (int) $value;
                }
            }
            $tempConversionsKeyedByCampaignId[$conversion['campaignId']][] = $conversion;
            $tempConversionsKeyedByUnix[$conversion['unix']][]             = $conversion;
        }

        /**
         * Iterate through Type query to get Type and Credit values
         */

        foreach ($queryResponse as $k => $marketingDeliverable) {

            $date = new \DateTime($marketingDeliverable['createdAt']->date);
            $date->modify('first day of this month');
            $marketingDeliverable['unix'] = $date->getTimestamp();

            if (!is_bool(array_search('processed', array_column($marketingDeliverable['events'], 'event'))) || !is_bool(array_search('sent', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']][$marketingDeliverable['type'] . 'Sent'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals'][$marketingDeliverable['type'] . 'Sent']                                += 1;
                $response['totals'][$marketingDeliverable['type'] . 'Sent']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']][$marketingDeliverable['type'] . 'Sent']                                         += 1;

                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['sent'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['sent']                                += 1;
                $response['totals']['sent']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['sent']                                         += 1;
            }

            if (!is_bool(array_search('bounce', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['bounce'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['bounce']                                += 1;
                $response['totals']['bounce']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['bounce']                                         += 1;
            }

            if (!is_bool(array_search('deferred', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['deferred'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['deferred']                                += 1;
                $response['totals']['deferred']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['deferred']                                         += 1;
            }

            if (!is_bool(array_search('click', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['click'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['click']                                += 1;
                $response['totals']['click']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['click']                                         += 1;
            }


            if (!is_bool(array_search('delivered', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['delivered'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['delivered']                                += 1;
                $response['totals']['delivered']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['delivered']                                         += 1;

                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']][$marketingDeliverable['type'] . 'Delivered'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals'][$marketingDeliverable['type'] . 'Delivered']                                += 1;
                $response['totals'][$marketingDeliverable['type'] . 'Delivered']                                                                                  += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']][$marketingDeliverable['type'] . 'Delivered']                                         += 1;
            }

            if (!is_bool(array_search('open', array_column($marketingDeliverable['events'], 'event')))) {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['opens'] += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['opens']
                    += 1;
                $response['totals']['opens']
                    += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['opens']
                    += 1;
            }


            $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['spendPerHead'] = $marketingDeliverable['spendPerHead'];


            if ($marketingDeliverable['type'] === 'sms') {
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['credits']                                += 2;
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['credits'] += 2;
                $response['totals']['charts'][$marketingDeliverable['unix']]['credits']                                         += 2;
                $response['totals']['credits']                                                                                  += 2;
            } else {
                $response['campaigns'][$marketingDeliverable['campaignId']]['charts'][$marketingDeliverable['unix']]['credits'] += 1;
                $response['totals']['charts'][$marketingDeliverable['unix']]['credits']                                         += 1;
                $response['totals']['credits']                                                                                  += 1;
                $response['campaigns'][$marketingDeliverable['campaignId']]['totals']['credits']                                += 1;
            }
        }

        /**
         * Iterate through to get Conversions
         */

        foreach ($response['campaigns'] as $key => $campaign) {

            if (!isset($tempConversionsKeyedByCampaignId[$key])) {
                continue;
            }

            $response['campaigns'][$key]['totals']['smsReturns']    += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'smsReturns'
            ));
            $response['campaigns'][$key]['totals']['emailReturns']  += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'emailReturns'
            ));
            $response['campaigns'][$key]['totals']['uniqueReturns'] += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'uniqueReturns'
            ));

            $response['totals']['uniqueReturns'] += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'uniqueReturns'
            ));
            $response['totals']['smsReturns']    += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'smsReturns'
            ));
            $response['totals']['emailReturns']  += array_sum(array_column(
                $tempConversionsKeyedByCampaignId[$key],
                'emailReturns'
            ));


            foreach ($tempConversionsKeyedByUnix as $ke => $keyedByUnix) {
                foreach ($keyedByUnix as $byUnix) {
                    if ($byUnix['campaignId'] !== $key) {
                        continue;
                    }
                    if (!array_key_exists($byUnix['unix'], $response['totals']['charts'])) {
                        continue;
                    }
                    $response['totals']['charts'][$byUnix['unix']]['smsReturns']    += $byUnix['smsReturns'];
                    $response['totals']['charts'][$byUnix['unix']]['emailReturns']  += $byUnix['emailReturns'];
                    $response['totals']['charts'][$byUnix['unix']]['uniqueReturns'] += $byUnix['uniqueReturns'];
                }
            }
        }

        foreach ($response['campaigns'] as $key => $value) {
            if ($response['campaigns'][$key]['totals']['uniqueReturns'] > 0 && $response['campaigns'][$key]['totals']['delivered'] > 0) {
                $conversion                                          = round(($response['campaigns'][$key]['totals']['uniqueReturns'] / $response['campaigns'][$key]['totals']['delivered']) * 100,
                    2
                );
                $response['campaigns'][$key]['totals']['conversion'] += $conversion;
                $response['campaigns'][$key]['totals']['roi']        = ($response['campaigns'][$key]['totals']['uniqueReturns'] * $response['campaigns'][$key]['totals']['spendPerHead']);
            }
        }

        foreach ($response['totals']['charts'] as $key => $value) {
            if ($response['totals']['charts'][$key]['uniqueReturns'] > 0 && $response['totals']['charts'][$key]['delivered'] > 0) {
                $conversion                                       = round(($response['totals']['charts'][$key]['uniqueReturns'] / $response['totals']['charts'][$key]['delivered']) * 100,
                    2
                );
                $response['totals']['charts'][$key]['conversion'] += $conversion;

                foreach ($response['campaigns'] as $ke => $val) {
                    $response['totals']['roi'] = ($response['totals']['uniqueReturns'] * $response['totals']['spendPerHead']);
                }
            }
        }
        if ($response['totals']['delivered'] > 0) {
            $conversion                       = round(($response['totals']['uniqueReturns'] / $response['totals']['delivered']) * 100,
                2
            );
            $response['totals']['conversion'] += $conversion;

            foreach ($response['campaigns'] as $key => $value) {

                $response['totals']['spendPerHead'] += $response['campaigns'][$key]['totals']['spendPerHead'];
            }

            $response['totals']['spendPerHead'] /= sizeof($response['campaigns']);

            $response['totals']['roi'] = ($response['totals']['uniqueReturns'] * $response['totals']['spendPerHead']);
        }

        // TODO - should we set this to something?
        $response['credits'] = 0;

        if (!is_null($offset)) {
            $return = [
                'conversions' => $response,
                'has_more'    => false,
                'total'       => count($results),
                'next_offset' => $offset + 20
            ];

            if ($offset <= $return['total'] && count($query) !== $return['total']) {
                $return['has_more'] = true;
            }

            return Http::status(200, $return);
        }

        return Http::status(200, $response);
    }

    private function generateMonths(int $numberOfMonths)
    {
        $dataStructure = [];
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $newDatetime                            = new \DateTime('first day of this month');
            $startTime                              = $newDatetime->modify('-' . $i . ' months');
            $dataStructure[$startTime->format('U')] = [
                'smsSent'        => 0,
                'emailSent'      => 0,
                'smsDelivered'   => 0,
                'emailDelivered' => 0,
                'sent'           => 0,
                'delivered'      => 0,
                'bounce'         => 0,
                'deferred'       => 0,
                'click'          => 0,
                'conversion'     => 0,
                'uniqueReturns'  => 0,
                'smsReturns'     => 0,
                'emailReturns'   => 0,
                'credits'        => 0,
                'opens'          => 0
            ];
        }

        return $dataStructure;
    }

    public function loadCampaignInfo(
        $serial,
        int $start,
        int $end,
        bool $export,
        string $user,
        ?int $offset,
        ?string $campaignId
    ) {
        $maxResults = 50;
        $startTime  = new \DateTime();
        $endTime    = new \DateTime();

        $startTime->setTimestamp($start);
        $endTime->setTimestamp($end);

        $startTime->setTime(0, 0, 0);
        $endTime->setTime(23, 59, 59);

        $query = $this->em->createQueryBuilder()
            ->select('me')
            ->from(MarketingEvents::class, 'me')
            ->where('me.serial IN (:serials)')
            ->setParameter('serials', $serial);
        if (!is_null($campaignId)) {
            $query = $query->andWhere('me.campaignId = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }
        $query = $query->andWhere('me.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $startTime)
            ->setParameter('end', $endTime)
            ->groupBy('me.id')
            ->orderBy('me.id', 'DESC');

        if ($export === false) {
            $query = $query
                ->setFirstResult($offset)
                ->setMaxResults($maxResults);
        }

        $results = new Paginator($query);
        $results->setUseOutputWalkers(false);

        $page = $results->getIterator()->getArrayCopy();

        if (empty($page)) {
            return Http::status(204);
        }

        $additionalInfo = [];

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + $maxResults,
            'users'       => []
        ];

        if ($offset <= $return['total'] && count($page) !== $return['total']) {
            $return['has_more'] = true;
        }

        foreach ($page as $item2) {
            $item = $item2->getArrayCopy();
            if (!isset($additionalInfo[$item['serial']])) {
                $additionalInfo[$item['serial']] = [
                    'emails'  => 0,
                    'credits' => 0,
                    'sms'     => 0
                ];
            }

            unset($item['id']);

            if ($item['type'] === 'sms') {
                $additionalInfo[$item['serial']]['sms']     += 1;
                $additionalInfo[$item['serial']]['credits'] += 2;
            } elseif ($item['type'] === 'email') {
                $additionalInfo[$item['serial']]['emails']  += 1;
                $additionalInfo[$item['serial']]['credits'] += 1;
            }

            $return['users'][] = $item;
        }

        if ($export === true) {
            $path    = 'campaign/' . $user . '/' . $startTime->getTimestamp() . '/' . $endTime->getTimestamp();
            $headers = [
                'Type',
                'Recipient',
                'Profile ID',
                'Date',
                'Serial',
                'Event ID',
                'Campaign ID'
            ];
            $this->newUploadController->generateCsv(
                $headers,
                $return['users'],
                $path,
                'campaign'
            );

            $retrieveFile = $this->fileExists($path, 'campaign');
            if (is_string($retrieveFile)) {
                return Http::status(200, ['export' => $retrieveFile]);
            }
        }

        $return['additionalInfo'] = $additionalInfo;

        return Http::status(200, $return);
    }

    public function fileExists(string $path, string $kind)
    {
        $fileCheck = $this->newUploadController->checkFile($path, $kind);
        if ($fileCheck['status'] === 200) {
            return substr($fileCheck['message'], 0, strlen($fileCheck['message']) - 4);
        }

        return false;
    }
}
